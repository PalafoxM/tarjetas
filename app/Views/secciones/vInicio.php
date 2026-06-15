<div class="fic-access-page">
    <header class="fic-access-header">
        <h1>Centro de Acceso FIC</h1>
        <p>
            Esta vista centraliza el acceso a los dashboards del sistema. Los permisos cambian según el perfil autenticado.
        </p>
    </header>

    <section class="fic-access-grid" aria-label="Accesos principales">
        <article class="fic-access-card fic-access-card--blue">
            <div>
                <span class="fic-access-card__category">Presupuesto</span>
                <h2>Partidas</h2>
                <p>Monitorea presupuesto, ejercido, disponible y actividad operativa por partida en una sola vista.</p>
            </div>
            <a class="fic-access-card__button" href="javascript:void(0);">
                Ver partidas
            </a>
        </article>

        <article class="fic-access-card fic-access-card--slate">
            <div>
                <span class="fic-access-card__category">Administración</span>
                <h2>Usuarios</h2>
                <p>Accede al catálogo de usuarios del sistema.</p>
            </div>
            <a class="fic-access-card__button" href="<?php echo base_url('index.php/Inicio/Usuarios'); ?>">
                Usuarios
            </a>
        </article>

        <article class="fic-access-card fic-access-card--cyan">
            <div>
                <span class="fic-access-card__category">Catálogo</span>
                <h2>Establecimientos FIC</h2>
                <p>Da de alta proveedores y después registra o administra sus establecimientos relacionados.</p>
            </div>
            <a class="fic-access-card__button" href="javascript:void(0);">
                Establecimientos FIC
            </a>
        </article>

        <article class="fic-access-card fic-access-card--purple">
            <div>
                <span class="fic-access-card__category">Dashboard</span>
                <h2>Pagos FIC</h2>
                <p>Consulta pagos, movimientos y evidencia PDF/XML asociada a cada transacción.</p>
            </div>
            <a class="fic-access-card__button" href="javascript:void(0);">
                Consultar pagos
            </a>
        </article>

        <article class="fic-access-card fic-access-card--green">
            <div>
                <span class="fic-access-card__category">Solicitudes</span>
                <h2>Solicitudes</h2>
                <p>Aprueba o rechaza solicitudes de QR, altas y órdenes de hospedaje enviadas al sistema.</p>
            </div>
            <a class="fic-access-card__button" href="javascript:void(0);">
                Ver solicitudes
            </a>
        </article>

        <article class="fic-access-card fic-access-card--outline">
            <div>
                <span class="fic-access-card__category">Hospedaje</span>
                <h2>Movimientos de hospedaje</h2>
                <p>Consulta los movimientos de todos los hoteles con las mismas capacidades operativas del perfil de recepción.</p>
            </div>
            <a class="fic-access-card__button" href="javascript:void(0);">
                Ver hospedajes
            </a>
        </article>
    </section>

    <section class="fic-reference-section" aria-labelledby="fic-reference-title">
        <header class="fic-reference-header">
            <h2 id="fic-reference-title">Accesos de interfaz</h2>
            <p>Accesos de referencia visibles solo para TI para revisar cómo se presentan las distintas interfaces del sistema.</p>
        </header>

        <div class="fic-access-grid" aria-label="Accesos de referencia">
            <article class="fic-access-card fic-access-card--reference">
                <div>
                    <span class="fic-access-card__category">Vista de referencia</span>
                    <h2>Ver perfil GESTOR</h2>
                    <p>Dashboard compartido de gestión presupuestal y catálogos.</p>
                </div>
                <a class="fic-access-card__button" href="javascript:void(0);">Abrir interfaz</a>
            </article>

            <article class="fic-access-card fic-access-card--reference">
                <div>
                    <span class="fic-access-card__category">Vista de referencia</span>
                    <h2>Ver perfil PROVEEDOR</h2>
                    <p>Interfaz operativa de pagos y movimientos con enfoque comercial.</p>
                </div>
                <a class="fic-access-card__button" href="javascript:void(0);">Abrir interfaz</a>
            </article>

            <article class="fic-access-card fic-access-card--reference">
                <div>
                    <span class="fic-access-card__category">Vista de referencia</span>
                    <h2>Ver perfil RECEPCIÓN</h2>
                    <p>Vista de hospedaje con alcance global para todos los hoteles.</p>
                </div>
                <a class="fic-access-card__button" href="javascript:void(0);">Abrir interfaz</a>
            </article>

            <article class="fic-access-card fic-access-card--reference">
                <div>
                    <span class="fic-access-card__category">Vista de referencia</span>
                    <h2>Ver perfil CAJERO</h2>
                    <p>Consulta de expedientes, QR y beneficios en modo referencia TI.</p>
                </div>
                <a class="fic-access-card__button" href="<?php echo base_url('index.php/Agregar/vistaCajero'); ?>">Abrir interfaz</a>
            </article>

            <article class="fic-access-card fic-access-card--reference">
                <div>
                    <span class="fic-access-card__category">Vista de referencia</span>
                    <h2>Ver perfil CLIENTE</h2>
                    <p>Consulta de QR, saldo y consumos de un cliente de referencia.</p>
                </div>
                <a class="fic-access-card__button" href="javascript:void(0);">Abrir interfaz</a>
            </article>

            <article class="fic-access-card fic-access-card--reference">
                <div>
                    <span class="fic-access-card__category">Vista de referencia</span>
                    <h2>Ver perfil SECUL</h2>
                    <p>Dashboard institucional restringido para SECUL.</p>
                </div>
                <a class="fic-access-card__button" href="javascript:void(0);">Abrir interfaz</a>
            </article>

            <article class="fic-access-card fic-access-card--reference">
                <div>
                    <span class="fic-access-card__category">Vista de referencia</span>
                    <h2>Ver perfil FIC</h2>
                    <p>Dashboard institucional restringido para usuarios FIC.</p>
                </div>
                <a class="fic-access-card__button" href="javascript:void(0);">Abrir interfaz</a>
            </article>

            <article class="fic-access-card fic-access-card--reference">
                <div>
                    <span class="fic-access-card__category">Vista de referencia</span>
                    <h2>Ver perfil UG</h2>
                    <p>Dashboard institucional restringido para UG.</p>
                </div>
                <a class="fic-access-card__button" href="javascript:void(0);">Abrir interfaz</a>
            </article>
        </div>
    </section>
</div>

<style>
    .fic-access-page {
        min-height: calc(100vh - 70px);
        padding: 34px 28px 48px;
        background:
            radial-gradient(circle at 82% 8%, rgba(54, 187, 225, .08), transparent 28%),
            #111b2a;
        color: #f8fafc;
    }

    .fic-access-header {
        max-width: 900px;
        margin-bottom: 28px;
    }

    .fic-reference-section {
        margin-top: 42px;
    }

    .fic-reference-header {
        margin-bottom: 26px;
    }

    .fic-reference-header h2 {
        margin: 0 0 5px;
        color: #fff;
        font-size: 1.55rem;
        font-weight: 700;
    }

    .fic-reference-header p {
        margin: 0;
        color: #c9d4e5;
        font-size: .9rem;
    }

    .fic-access-eyebrow,
    .fic-access-card__category {
        color: #69b8ff;
        font-size: .76rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .fic-access-header h1 {
        margin: 4px 0 5px;
        color: #fff;
        font-size: clamp(1.8rem, 4vw, 2.45rem);
        font-weight: 700;
    }

    .fic-access-header p {
        margin: 0;
        color: #c9d4e5;
        font-size: .95rem;
    }

    .fic-access-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(220px, 1fr));
        gap: 16px;
    }

    .fic-access-card {
        --card-accent: #f8fafc;
        display: flex;
        min-height: 228px;
        padding: 22px;
        flex-direction: column;
        justify-content: space-between;
        border: 1px solid #344155;
        border-radius: 20px;
        background: linear-gradient(145deg, #1d2939, #121c2b);
        box-shadow: 0 12px 30px rgba(0, 0, 0, .14);
        transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
    }

    .fic-access-card:hover {
        transform: translateY(-4px);
        border-color: color-mix(in srgb, var(--card-accent) 55%, #344155);
        box-shadow: 0 18px 34px rgba(0, 0, 0, .25);
    }

    .fic-access-card h2 {
        margin: 10px 0 8px;
        color: #fff;
        font-size: 1.18rem;
        font-weight: 700;
    }

    .fic-access-card p {
        margin: 0 0 18px;
        color: #c9d4e5;
        font-size: .9rem;
        line-height: 1.48;
    }

    .fic-access-card__button {
        display: block;
        padding: 10px 14px;
        border: 1px solid var(--card-accent);
        border-radius: 12px;
        background: var(--card-accent);
        color: #111827;
        font-size: .88rem;
        font-weight: 700;
        text-align: center;
        transition: filter .2s ease, transform .2s ease;
    }

    .fic-access-card__button:hover {
        color: #111827;
        filter: brightness(1.08);
        transform: translateY(-1px);
    }

    .fic-access-card--blue { --card-accent: #e9eef5; }
    .fic-access-card--slate { --card-accent: #cbd5e1; }
    .fic-access-card--cyan { --card-accent: #39b3d1; }
    .fic-access-card--purple { --card-accent: #7377f4; }
    .fic-access-card--green { --card-accent: #10cba1; }
    .fic-access-card--outline { --card-accent: #7c83ff; }
    .fic-access-card--reference { --card-accent: #f8fafc; }

    .fic-access-card--outline .fic-access-card__button,
    .fic-access-card--reference .fic-access-card__button {
        background: transparent;
        color: var(--card-accent);
    }

    .fic-access-card--outline .fic-access-card__button:hover,
    .fic-access-card--reference .fic-access-card__button:hover {
        background: var(--card-accent);
        color: #111827;
    }

    @media (max-width: 1199.98px) {
        .fic-access-grid {
            grid-template-columns: repeat(3, minmax(220px, 1fr));
        }
    }

    @media (max-width: 899.98px) {
        .fic-access-grid {
            grid-template-columns: repeat(2, minmax(220px, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .fic-access-page {
            padding: 24px 15px 36px;
        }

        .fic-access-grid {
            grid-template-columns: 1fr;
        }

        .fic-access-card {
            min-height: 210px;
        }
    }
</style>
