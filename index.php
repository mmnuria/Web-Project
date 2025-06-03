<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/funciones.php';

// Obtener salas reservables
$stmtSalas = $pdo->prepare("SELECT * FROM salas WHERE reservable = 1");
$stmtSalas->execute();
$salas = $stmtSalas->fetchAll(PDO::FETCH_ASSOC);

// Obtener fotos para esas salas
$salaIds = array_column($salas, 'id');
$fotosPorSala = [];
if (!empty($salaIds)) {
    $placeholders = implode(',', array_fill(0, count($salaIds), '?'));
    $stmtFotos = $pdo->prepare("SELECT sala_id, foto, tipo_mime FROM fotos_salas WHERE sala_id IN ($placeholders) ORDER BY sala_id");
    $stmtFotos->execute($salaIds);
    $fotosRaw = $stmtFotos->fetchAll(PDO::FETCH_ASSOC);
    foreach ($fotosRaw as $foto) {
        $fotosPorSala[$foto['sala_id']][] = [
            'mime' => $foto['tipo_mime'],
            'foto' => base64_encode($foto['foto'])
        ];
    }
}

// Obtener reservas
$stmtReservas = $pdo->prepare("
    SELECT r.*, s.nombre AS sala_nombre, u.nombre AS usuario_nombre
    FROM reservas r
    INNER JOIN salas s ON r.sala_id = s.id
    INNER JOIN usuarios u ON r.usuario_id = u.id
    WHERE s.reservable = 1
    ORDER BY r.fecha, r.hora_inicio
");
$stmtReservas->execute();
$reservas = $stmtReservas->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Salas</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/popup.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/nav.php'; ?>

    <div class="container">
        <main>
            <h1>
                Bienvenido<?php
                if (isset($_SESSION['user']) && !empty($_SESSION['user']['nombre'])) {
                    echo " " .htmlspecialchars($_SESSION['user']['nombre']);
                }
                ?> a la plataforma de reservas de salas
            </h1>

            <!-- SALAS DISPONIBLES -->
            <section>
                <h2>Salas disponibles</h2>
                <div class="salas-grid">
                    <?php foreach ($salas as $sala): ?>
                        <div class="sala-card" data-sala-id="<?= (int) $sala['id'] ?>">
                            <h3><?= htmlspecialchars($sala['nombre']); ?></h3>
                            <p><strong>Capacidad:</strong> <?= htmlspecialchars($sala['num_puestos']); ?></p>
                            <p><strong>Ubicación:</strong> <?= htmlspecialchars($sala['ubicacion']); ?></p>
                            <p><?= nl2br(htmlspecialchars($sala['descripcion'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- RESERVAS RECIENTES -->
            <section>
                <h2>Reservas recientes</h2>
                <div class="reservas-lista">
                    <?php
                    $max_mostrar = 3;
                    $total_reservas = count($reservas);
                    $mostrar_reservas = array_slice($reservas, 0, $max_mostrar);
                    ?>
                    <ul>
                        <?php foreach ($mostrar_reservas as $reserva): ?>
                            <li>
                                <strong><?= htmlspecialchars($reserva['sala_nombre']) ?></strong> —
                                <?= htmlspecialchars($reserva['fecha']) ?>
                                <?= formatHoraSinSegundos($reserva['hora_inicio']) ?> -
                                <?= formatHoraSinSegundos($reserva['hora_fin']) ?> —
                                <?= nl2br(htmlspecialchars($reserva['motivo'])) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($total_reservas > $max_mostrar): ?>
                        <p>
                            <a href="busqueda_reservas.php" class="btn-ver-mas">Ver todas las reservas</a>
                        </p>
                    <?php endif; ?>
                </div>
            </section>
        </main>

        <aside>
            <?php include 'includes/sidebar.php'; ?>
        </aside>
    </div>

    <!-- MODAL -->
    <div id="sala-info-modal" class="popup-modal" style="display:none;">
        <div class="popup-content">
            <button id="modal-close-btn" class="popup-close" aria-label="Cerrar">&times;</button>
            <div id="modal-sala-info"></div>
            <div id="modal-fotos-container">
                <button id="prev-foto" style="display:none;">&#8592;</button>
                <img id="modal-foto" src="" alt="Foto sala">
                <button id="next-foto" style="display:none;">&#8594;</button>
            </div>
        </div>
    </div>

    <script>
        const salasData = <?php
        $data = [];
        foreach ($salas as $s) {
            $data[$s['id']] = [
                'nombre' => $s['nombre'],
                'num_puestos' => $s['num_puestos'],
                'ubicacion' => $s['ubicacion'],
                'descripcion' => $s['descripcion'],
                'reservable' => $s['reservable'],
                'fotos' => $fotosPorSala[$s['id']] ?? []
            ];
        }
        echo json_encode($data);
        ?>;

        const modal = document.getElementById('sala-info-modal');
        const modalContent = document.getElementById('modal-sala-info');
        const modalFoto = document.getElementById('modal-foto');
        const prevBtn = document.getElementById('prev-foto');
        const nextBtn = document.getElementById('next-foto');
        const closeBtn = document.getElementById('modal-close-btn');

        let fotoIndex = 0;
        let fotosActuales = [];

        function openModal(salaId) {
            const sala = salasData[salaId];
            if (!sala || parseInt(sala.reservable) !== 1) return;

            modalContent.innerHTML = `
        <h2>${sala.nombre}</h2>
        <p><strong>Capacidad:</strong> ${sala.num_puestos}</p>
        <p><strong>Ubicación:</strong> ${sala.ubicacion}</p>
        <p>${sala.descripcion.replace(/\n/g, '<br>')}</p>
    `;

            fotosActuales = sala.fotos;
            fotoIndex = 0;

            if (fotosActuales.length > 0) {
                modalFoto.src = "data:" + fotosActuales[fotoIndex].mime + ";base64," + fotosActuales[fotoIndex].foto;
                modalFoto.style.display = 'block';

                prevBtn.style.display = fotosActuales.length > 1 ? 'inline-block' : 'none';
                nextBtn.style.display = fotosActuales.length > 1 ? 'inline-block' : 'none';
            } else {
                modalFoto.style.display = 'none';
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'none';
            }

            modal.style.display = 'flex';
        }

        function mostrarFoto(index) {
            if (fotosActuales.length === 0) return;
            fotoIndex = (index + fotosActuales.length) % fotosActuales.length;
            modalFoto.src = "data:" + fotosActuales[fotoIndex].mime + ";base64," + fotosActuales[fotoIndex].foto;
        }

        prevBtn.addEventListener('click', () => mostrarFoto(fotoIndex - 1));
        nextBtn.addEventListener('click', () => mostrarFoto(fotoIndex + 1));
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
            modalContent.innerHTML = '';
            modalFoto.src = '';
        });
        modal.addEventListener('click', e => {
            if (e.target === modal) {
                modal.style.display = 'none';
                modalContent.innerHTML = '';
                modalFoto.src = '';
            }
        });

        document.querySelectorAll('.sala-card').forEach(card => {
            card.addEventListener('click', () => {
                const salaId = card.getAttribute('data-sala-id');
                openModal(salaId);
            });
        });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>

</html>