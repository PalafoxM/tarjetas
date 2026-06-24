<?php

namespace App\Libraries;

class UsuarioPerfilResolver
{
    private const GROUPS = [
        'fic' => [
            'field' => 'id_fic_perfil',
            'label' => 'FIC',
            'roles' => [
                1 => 'Admin',
                2 => 'Capturista',
                3 => 'Cliente',
                4 => 'Administrativo',
            ],
            'client_roles' => [3, 4],
        ],
        'secul' => [
            'field' => 'id_secul_perfil',
            'label' => 'SECUL',
            'roles' => [
                1 => 'Admin',
                2 => 'Capturista',
                3 => 'Cliente',
                4 => 'Administrativo',
            ],
            'client_roles' => [3, 4],
        ],
        'ug' => [
            'field' => 'id_ug_perfil',
            'label' => 'UG',
            'roles' => [
                1 => 'Admin',
                2 => 'Capturista',
                3 => 'Cliente',
                4 => 'Administrativo',
            ],
            'client_roles' => [3, 4],
        ],
        'secturi' => [
            'field' => 'id_secturi_perfil',
            'label' => 'SECTURI',
            'roles' => [
                1 => 'Admin',
                2 => 'Capturista',
                4 => 'Cajero',
                5 => 'Cliente',
            ],
            'client_roles' => [5],
        ],
    ];

    private const PROVIDER_TYPES = [
        1 => 'Proveedor',
        2 => 'Gerente',
        3 => 'Recepcion',
    ];

    public function resolve($source): array
    {
        $row = is_object($source) ? get_object_vars($source) : (array) $source;
        $idPerfil = $this->intValue($row['id_perfil'] ?? null);
        $idProveedor = $this->intValue($row['id_proveedor'] ?? null);
        $providerType = $this->intValue($row['id_tipo_proveedor'] ?? null);

        if ($providerType <= 0) {
            $providerType = $this->resolveProviderTypeFromLocalUser($row);
        }
        if ($idPerfil === 1 && $providerType === 0 && $idProveedor === 0) {
            return [
                'id_perfil' => $idPerfil,
                'id_tipo_proveedor' => $providerType,
                'provider_label' => null,
                'active_group' => 'ti',
                'group_label' => 'TI',
                'group_field' => null,
                'group_role' => 1,
                'group_role_label' => 'TI Master',
                'is_provider_flow' => false,
                'is_recepcion_flow' => false,
                'is_cajero_flow' => false,
                'is_client_like' => false,
                'is_group_admin' => true,
                'is_group_capturista' => false,
                'is_group_backoffice' => true,
                'is_secturi_cajero' => false,
                'is_ti_master' => true,
                'can_access_user_catalog' => true,
                'can_edit_user_catalog' => true,
            ];
        }


        $activeGroup = null;
        $groupRole = 0;
        foreach (self::GROUPS as $groupKey => $config) {
            $role = $this->intValue($row[$config['field']] ?? null);
            if ($role > 0) {
                $activeGroup = $groupKey;
                $groupRole = $role;
                break;
            }
        }

        $groupConfig = $activeGroup ? self::GROUPS[$activeGroup] : null;
        $roleLabel = $groupConfig['roles'][$groupRole] ?? null;
        $providerLabel = self::PROVIDER_TYPES[$providerType] ?? null;
        $isClientLike = $activeGroup
            ? in_array($groupRole, $groupConfig['client_roles'], true)
            : $idPerfil === 3;
        $isGroupAdmin = $activeGroup !== null && $groupRole === 1;
        $isGroupCapturista = $activeGroup !== null && $groupRole === 2;
        $isSecturiCajero = $activeGroup === 'secturi' && $groupRole === 4;

        return [
            'id_perfil' => $idPerfil,
            'id_tipo_proveedor' => $providerType,
            'provider_label' => $providerLabel,
            'active_group' => $activeGroup,
            'group_label' => $groupConfig['label'] ?? null,
            'group_field' => $groupConfig['field'] ?? null,
            'group_role' => $groupRole,
            'group_role_label' => $roleLabel,
            'is_provider_flow' => $idProveedor > 0 || in_array($providerType, [1, 2], true) || $idPerfil === 2,
            'is_recepcion_flow' => $providerType === 3 || $idPerfil === 7,
            'is_cajero_flow' => $idPerfil === 6 || $isSecturiCajero,
            'is_client_like' => $isClientLike,
            'is_group_admin' => $isGroupAdmin,
            'is_group_capturista' => $isGroupCapturista,
            'is_group_backoffice' => $isGroupAdmin || $isGroupCapturista,
            'is_secturi_cajero' => $isSecturiCajero,
            'is_ti_master' => $idPerfil === 1 && $providerType === 0 && $idProveedor === 0,
            'can_access_user_catalog' => ($idPerfil === 1 && $providerType === 0 && $idProveedor === 0) || $isGroupAdmin || $isGroupCapturista || $isSecturiCajero,
            'can_edit_user_catalog' => ($idPerfil === 1 && $providerType === 0 && $idProveedor === 0) || $isGroupAdmin,
        ];
    }

    public function getDefinitions(): array
    {
        return self::GROUPS;
    }

    public function getProviderTypes(): array
    {
        return self::PROVIDER_TYPES;
    }

    public function canViewRow(array $actorContext, $row): bool
    {
        if ($actorContext['is_ti_master'] || (int) ($actorContext['id_perfil'] ?? 0) === 1) {
            return true;
        }

        $targetContext = $this->resolve($row);
        if ($targetContext['id_tipo_proveedor'] > 0) {
            return false;
        }

        if ($actorContext['is_secturi_cajero']) {
            return $targetContext['active_group'] === 'secturi';
        }

        if (!$actorContext['active_group']) {
            return false;
        }

        return $targetContext['active_group'] === $actorContext['active_group'];
    }

    public function canMutateRow(array $actorContext, $row): bool
    {
        if (!$actorContext['can_edit_user_catalog']) {
            return false;
        }

        if ($actorContext['is_ti_master'] || (int) ($actorContext['id_perfil'] ?? 0) === 1) {
            return true;
        }

        $targetContext = $this->resolve($row);
        if ($targetContext['id_tipo_proveedor'] > 0) {
            return false;
        }

        return $actorContext['active_group'] && $targetContext['active_group'] === $actorContext['active_group'];
    }

    public function getAllowedRoleOptions(array $actorContext): array
    {
        if ($actorContext['is_ti_master']) {
            $options = [];
            foreach (self::GROUPS as $groupKey => $config) {
                $options[$groupKey] = [
                    'label' => $config['label'],
                    'roles' => $config['roles'],
                ];
            }

            return $options;
        }

        if ($actorContext['active_group'] && isset(self::GROUPS[$actorContext['active_group']])) {
            $config = self::GROUPS[$actorContext['active_group']];

            return [
                $actorContext['active_group'] => [
                    'label' => $config['label'],
                    'roles' => $config['roles'],
                ],
            ];
        }

        return [];
    }

    public function applyAssignment(array $payload, array $actorContext, ?array $existingRow = null): array
    {
        $existingRow = $existingRow ?? [];
        $groupKey = trim((string) ($payload['grupo_usuario'] ?? ''));
        $groupRole = $this->intValue($payload['perfil_grupo'] ?? null);
        $providerType = $this->intValue($payload['id_tipo_proveedor'] ?? null);

        $result = [
            'id_fic_perfil' => null,
            'id_secul_perfil' => null,
            'id_ug_perfil' => null,
            'id_secturi_perfil' => null,
            'id_tipo_proveedor' => null,
        ];

        if (!$actorContext['is_ti_master']) {
            $groupKey = (string) ($actorContext['active_group'] ?? '');
            $providerType = 0;
        }

        if ($groupKey === 'proveedor') {
            $result['id_tipo_proveedor'] = in_array($providerType, [1, 2, 3], true)
                ? $providerType
                : $this->intValue($existingRow['id_tipo_proveedor'] ?? null);

            return $result;
        }

        if (!isset(self::GROUPS[$groupKey])) {
            return $existingRow ? [
                'id_fic_perfil' => $existingRow['id_fic_perfil'] ?? null,
                'id_secul_perfil' => $existingRow['id_secul_perfil'] ?? null,
                'id_ug_perfil' => $existingRow['id_ug_perfil'] ?? null,
                'id_secturi_perfil' => $existingRow['id_secturi_perfil'] ?? null,
                'id_tipo_proveedor' => $existingRow['id_tipo_proveedor'] ?? null,
            ] : $result;
        }

        $roles = self::GROUPS[$groupKey]['roles'];
        if (!isset($roles[$groupRole])) {
            $groupRole = $this->intValue($existingRow[self::GROUPS[$groupKey]['field']] ?? null);
        }

        if ($groupRole > 0) {
            $result[self::GROUPS[$groupKey]['field']] = $groupRole;
        }

        return $result;
    }

    public function inferLegacyProfile(array $assignment, ?array $existingRow = null): int
    {
        $providerType = $this->intValue($assignment['id_tipo_proveedor'] ?? null);
        if ($providerType > 0) {
            if ($providerType === 3) {
                return 7;
            }

            return 2;
        }

        if ($this->intValue($assignment['id_secturi_perfil'] ?? null) === 4) {
            return 6;
        }

        foreach (self::GROUPS as $groupKey => $config) {
            $role = $this->intValue($assignment[$config['field']] ?? null);
            if ($role === 1 || $role === 2) {
                return 1;
            }

            if ($role > 0 && in_array($role, $config['client_roles'], true)) {
                return 3;
            }
        }

        return $this->intValue($existingRow['id_perfil'] ?? null) ?: 3;
    }

    public function decorateRow(array $row, array $actorContext): array
    {
        $targetContext = $this->resolve($row);
        $groupLabel = $targetContext['group_label'] ?? ($targetContext['provider_label'] ?: 'Sin grupo');
        $roleLabel = $targetContext['group_role_label'] ?? ($targetContext['provider_label'] ?: 'Sin perfil');

        $row['grupo_visible'] = $groupLabel;
        $row['rol_visible'] = $roleLabel;
        $row['permiso_editar'] = $this->canMutateRow($actorContext, $row) ? 1 : 0;
        $row['permiso_eliminar'] = $this->canMutateRow($actorContext, $row) ? 1 : 0;
        $row['solo_consulta'] = $row['permiso_editar'] ? 0 : 1;
        $row['es_cliente_like'] = $targetContext['is_client_like'] ? 1 : 0;

        return $row;
    }

    private function intValue($value): int
    {
        if ($value === null || $value === '' || $value === false) {
            return 0;
        }

        return (int) $value;
    }

    private function resolveProviderTypeFromLocalUser(array $row): int
    {
        $idUsuario = $this->intValue($row['id_usuario'] ?? null);
        $usuario = trim((string) ($row['usuario'] ?? ''));

        if ($idUsuario <= 0 && $usuario === '') {
            return 0;
        }

        try {
            $db = \Config\Database::connect();
            $builder = $db->table('usuario')
                ->select('id_tipo_proveedor')
                ->where('visible', 1);

            if ($idUsuario > 0) {
                $builder->where('id_usuario', $idUsuario);
            } else {
                $builder->where('usuario', $usuario);
            }

            $result = $builder->get()->getRowArray();
            return $this->intValue($result['id_tipo_proveedor'] ?? null);
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
