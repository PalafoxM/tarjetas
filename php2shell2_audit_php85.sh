#!/usr/bin/env bash
set -euo pipefail

GREEN="\033[0;32m"
YELLOW="\033[0;33m"
RED="\033[0;31m"
NC="\033[0m"

OUTPUT_FILE="paquetes_php.txt"
CVE_REGEX='CVE-2026-109618|CVE-2026-109619'

echo "Detectando sistema y exportando paquetes PHP a $OUTPUT_FILE..."

OS_TYPE=""

if command -v dpkg >/dev/null 2>&1; then
    OS_TYPE="DEB"
    dpkg -l | awk 'tolower($0) ~ /php/ {print}' > "$OUTPUT_FILE"
elif command -v rpm >/dev/null 2>&1; then
    OS_TYPE="RPM"
    rpm -qa | grep -i php > "$OUTPUT_FILE" || true
else
    echo -e "${RED}Gestor de paquetes no soportado.${NC}"
    exit 1
fi

version_is_upstream_fixed() {
    local version="$1"
    local major minor patch

    version="${version%%-*}"
    IFS='.' read -r major minor patch <<< "$version"
    patch="${patch:-0}"

    [[ "$major" =~ ^[0-9]+$ && "$minor" =~ ^[0-9]+$ && "$patch" =~ ^[0-9]+$ ]] || return 1

    if (( major != 8 )); then
        return 1
    fi

    case "$minor" in
        5) (( patch >= 12 )) ;;
        4) (( patch >= 27 )) ;;
        3) (( patch >= 36 )) ;;
        2) (( patch >= 35 )) ;;
        *) return 1 ;;
    esac
}

ubuntu_jammy_php81_fixed() {
    local package="$1"

    [[ "$OS_TYPE" == "DEB" ]] || return 1
    command -v lsb_release >/dev/null 2>&1 || return 1
    [[ "$(lsb_release -rs 2>/dev/null)" == "22.04" ]] || return 1

    local pkgver
    pkgver="$(dpkg-query -W -f='${Version}' "$package" 2>/dev/null || true)"
    [[ -n "$pkgver" ]] || return 1

    dpkg --compare-versions "$pkgver" ge "8.1.2-1ubuntu2.28"
}

package_changelog_has_fix() {
    local package="$1"

    if [[ "$OS_TYPE" == "DEB" ]]; then
        local doc="/usr/share/doc/${package}/changelog.Debian.gz"
        [[ -f "$doc" ]] && zgrep -iqE "$CVE_REGEX" "$doc"
    else
        rpm -q --changelog "$package" 2>/dev/null | grep -iqE "$CVE_REGEX"
    fi
}

package_for_binary() {
    local binary="$1"

    if [[ "$OS_TYPE" == "DEB" ]]; then
        dpkg-query -S "$binary" 2>/dev/null | head -n1 | cut -d: -f1
    else
        rpm -qf "$binary" 2>/dev/null || true
    fi
}

check_binary() {
    local label="$1"
    local binary="$2"

    if [[ ! -x "$binary" ]]; then
        echo -e "${YELLOW}${label}: no encontrado (${binary})${NC}"
        return 2
    fi

    local version package status="VULNERABLE/NO CONFIRMADO"

    version="$("$binary" -r 'echo PHP_VERSION;' 2>/dev/null)"
    package="$(package_for_binary "$binary")"

    if version_is_upstream_fixed "$version"; then
        status="CORREGIDO (versión upstream)"
    elif [[ -n "$package" ]] && ubuntu_jammy_php81_fixed "$package"; then
        status="CORREGIDO (backport Ubuntu 22.04)"
    elif [[ -n "$package" ]] && package_changelog_has_fix "$package"; then
        status="CORREGIDO (CVE encontrada en changelog del paquete)"
    fi

    if [[ "$status" == CORREGIDO* ]]; then
        echo -e "${GREEN}${label}: ${version} - ${status}${NC}"
        return 0
    fi

    echo -e "${RED}${label}: ${version} - ${status}${NC}"
    return 1
}

echo
echo "===== Runtime web de Apache ====="

WEB_PHP_BIN=""

if command -v apache2ctl >/dev/null 2>&1; then
    ACTIVE_PHP_CONF="$(find /etc/apache2/mods-enabled -maxdepth 1 -type l -name 'php*.load' -printf '%f\n' 2>/dev/null | head -n1 || true)"

    if [[ "$ACTIVE_PHP_CONF" =~ ^php([0-9]+\.[0-9]+)\.load$ ]]; then
        WEB_PHP_BIN="/usr/bin/php${BASH_REMATCH[1]}"
    fi
fi

WEB_OK=false

if [[ -n "$WEB_PHP_BIN" ]]; then
    if check_binary "Apache/mod_php" "$WEB_PHP_BIN"; then
        WEB_OK=true
    fi
else
    echo -e "${YELLOW}No se pudo determinar automáticamente el binario PHP del módulo Apache activo.${NC}"
fi

echo
echo "===== PHP CLI global ====="

CLI_OK=false

if command -v php >/dev/null 2>&1; then
    CLI_BIN="$(readlink -f "$(command -v php)")"

    if check_binary "CLI global" "$CLI_BIN"; then
        CLI_OK=true
    fi
else
    echo -e "${YELLOW}No se encontró el comando php global.${NC}"
fi

echo
echo "===== Binarios PHP instalados ====="

FOUND_ANY=false

for binary in /usr/bin/php8.*; do
    [[ -x "$binary" ]] || continue
    FOUND_ANY=true
    check_binary "Instalado $(basename "$binary")" "$binary" || true
done

if [[ "$FOUND_ANY" == false ]]; then
    echo "No se encontraron binarios /usr/bin/php8.*"
fi

echo
echo "===== Resultado ====="

if [[ "$WEB_OK" == true ]]; then
    echo -e "${GREEN}RUNTIME WEB: CORREGIDO para CVE-2026-109618 y CVE-2026-109619.${NC}"
else
    echo -e "${RED}RUNTIME WEB: NO SE PUDO CONFIRMAR COMO CORREGIDO.${NC}"
fi

if [[ "$CLI_OK" == true ]]; then
    echo -e "${GREEN}CLI GLOBAL: CORREGIDO para las CVE auditadas.${NC}"
else
    echo -e "${YELLOW}CLI GLOBAL: revisar por separado. Esto no implica que Apache sea vulnerable.${NC}"
fi

echo
echo "Inventario guardado en: $OUTPUT_FILE"

[[ "$WEB_OK" == true ]]
