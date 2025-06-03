<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Parámetros de paginación
$porPagina = 5;
$paginaActual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($paginaActual - 1) * $porPagina;

// Total de salas
$totalStmt = $pdo->query("SELECT COUNT(*) FROM salas");
$totalSalas = $totalStmt->fetchColumn();
$totalPaginas = ceil($totalSalas / $porPagina);

// Obtener salas paginadas ordenadas por nombre
$stmt = $pdo->prepare("SELECT * FROM salas ORDER BY nombre LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$salas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener fotos por sala
$stmtFotos = $pdo->query("SELECT sala_id, foto, tipo_mime FROM fotos_salas ORDER BY sala_id");
$fotosPorSala = [];
foreach ($stmtFotos->fetchAll(PDO::FETCH_ASSOC) as $foto) {
    $fotosPorSala[$foto['sala_id']][] = $foto;
}
// Obtener todos los comentarios sin agrupar
$stmtComentarios = $pdo->query("
    SELECT c.*, u.nombre AS usuario_nombre
    FROM comentarios_salas c
    JOIN usuarios u ON u.id = c.usuario_id
    ORDER BY c.fecha DESC
");
$comentariosRaw = $stmtComentarios->fetchAll(PDO::FETCH_ASSOC);

// Agrupar comentarios por sala_id
$comentarios = [];
foreach ($comentariosRaw as $coment) {
    $comentarios[$coment['sala_id']][] = $coment;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Listado de Salas</title>
    <link rel="stylesheet" href="css/styles.css" />
</head>

<body class="salas-body">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/nav.php'; ?>

    <main class="salas-main">
        <h1 class="salas-title">Listado de Salas</h1>

        <?php if (isset($_SESSION['user']) && $_SESSION['user']['rol'] === 'admin'): ?>
            <p class="salas-add-link">
                <a href="añadir_sala.php">➕ Añadir nueva sala</a>
            </p>
        <?php endif; ?>

        <div class="salas-table-wrapper">
            <table class="salas-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Ubicación</th>
                        <th>Puestos</th>
                        <th>Reservable</th>
                        <th>Fotos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salas as $sala): ?>
                        <tr>
                            <td><?= htmlspecialchars($sala['nombre']) ?></td>
                            <td><?= htmlspecialchars($sala['ubicacion']) ?></td>
                            <td><?= (int) $sala['num_puestos'] ?></td>
                            <td><?= $sala['reservable'] ? 'Sí' : 'No' ?></td>
                            <td class="sala-fotos" data-sala-id="<?= (int) $sala['id'] ?>">
                                <?php
                                if (!empty($fotosPorSala[$sala['id']])) {
                                    foreach ($fotosPorSala[$sala['id']] as $index => $foto) {
                                        $src = 'data:' . htmlspecialchars($foto['tipo_mime']) . ';base64,' . base64_encode($foto['foto']);
                                        echo "<img src='$src' class='foto-sala' alt='Foto de sala' data-sala-id='" . (int) $sala['id'] . "' data-index='$index' ";
                                    }
                                } else {
                                    echo "<span class='sin-fotos'>Sin fotos</span>";
                                }
                                ?>
                            </td>
                            <td>
                                <?php if (isset($_SESSION['user']) && $_SESSION['user']['rol'] === 'admin'): ?>
                                    <div class="acciones-admin">
                                        <a href="editar_sala.php?id=<?= (int) $sala['id'] ?>" class="btn-editar">✏️ Editar</a>
                                        <form method="POST" action="eliminar_sala.php" novalidate>
                                            <input type="hidden" name="sala_id" value="<?= (int) $sala['id'] ?>">
                                            <button type="submit" class="btn-eliminar">🗑️ Eliminar</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>

                        <tr class="fila-comentarios">
                            <td colspan="6">
                                <div class="comentarios">
                                    <strong>Comentarios:</strong>
                                    <ul>
                                        <?php if (!empty($comentarios[$sala['id']])): ?>
                                            <?php foreach ($comentarios[$sala['id']] as $coment): ?>
                                                <li>
                                                    <em><?= htmlspecialchars($coment['usuario_nombre']) ?>:</em>
                                                    <?= nl2br(htmlspecialchars($coment['comentario'])) ?>
                                                    <small>(<?= date('d/m/Y H:i', strtotime($coment['fecha'])) ?>)</small>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li><em>Sin comentarios.</em></li>
                                        <?php endif; ?>
                                    </ul>

                                    <?php if (isset($_SESSION['user'])): ?>
                                        <form method="POST" action="includes/agregar_comentario.php" class="form-comentario" novalidate>
                                            <input type="hidden" name="sala_id" value="<?= (int) $sala['id'] ?>">
                                            <textarea name="comentario" rows="2" placeholder="Escribe un comentario..." required></textarea>
                                            <button type="submit">💬 Comentar</button>
                                        </form>
                                    <?php else: ?>
                                        <p><em>Inicia sesión para dejar un comentario.</em></p>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="paginacion">
            <?php if ($paginaActual > 1): ?>
                <a href="?pagina=<?= $paginaActual - 1 ?>" class="btn-anterior">&laquo; Anterior</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <?php if ($i == $paginaActual): ?>
                    <span class="actual"><?= $i ?></span>
                <?php else: ?>
                    <a href="?pagina=<?= $i ?>" class="btn-paginas"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($paginaActual < $totalPaginas): ?>
                <a href="?pagina=<?= $paginaActual + 1 ?>" class="btn-siguiente">Siguiente &raquo;</a>
            <?php endif; ?>
        </div>
    </main>

    <!-- Popup modal para imágenes -->
    <div id="popup-img-modal">
        <button id="popup-close-btn" aria-label="Cerrar">&times;</button>
        <button id="popup-prev-btn" aria-label="Foto anterior">&#10094;</button>
        <img id="popup-img" src="" alt="Foto ampliada">
        <button id="popup-next-btn" aria-label="Foto siguiente">&#10095;</button>
    </div>

    <script>
        (() => {
            const fotosPorSala = <?php
            $jsonFotos = [];
            foreach ($fotosPorSala as $salaId => $fotos) {
                $jsonFotos[$salaId] = [];
                foreach ($fotos as $foto) {
                    $jsonFotos[$salaId][] = 'data:' . $foto['tipo_mime'] . ';base64,' . base64_encode($foto['foto']);
                }
            }
            echo json_encode($jsonFotos);
            ?>;

            const modal = document.getElementById('popup-img-modal');
            const modalImg = document.getElementById('popup-img');
            const closeBtn = document.getElementById('popup-close-btn');
            const prevBtn = document.getElementById('popup-prev-btn');
            const nextBtn = document.getElementById('popup-next-btn');

            let currentSalaId = null;
            let currentIndex = 0;

            function openModal(salaId, index) {
                currentSalaId = salaId;
                currentIndex = index;
                modalImg.src = fotosPorSala[salaId][index];
                modal.style.display = 'flex';
                updateButtons();
            }

            function updateButtons() {
                prevBtn.style.display = (currentIndex > 0) ? 'block' : 'none';
                nextBtn.style.display = (currentSalaId !== null && fotosPorSala[currentSalaId] && currentIndex < fotosPorSala[currentSalaId].length - 1) ? 'block' : 'none';
            }

            closeBtn.addEventListener('click', () => {
                modal.style.display = 'none';
                currentSalaId = null;
                currentIndex = 0;
                modalImg.src = '';
            });

            prevBtn.addEventListener('click', () => {
                if (currentIndex > 0) {
                    currentIndex--;
                    modalImg.src = fotosPorSala[currentSalaId][currentIndex];
                    updateButtons();
                }
            });

            nextBtn.addEventListener('click', () => {
                if (currentSalaId !== null && currentIndex < fotosPorSala[currentSalaId].length - 1) {
                    currentIndex++;
                    modalImg.src = fotosPorSala[currentSalaId][currentIndex];
                    updateButtons();
                }
            });

            document.querySelectorAll('.foto-sala').forEach(img => {
                img.addEventListener('click', () => {
                    const salaId = img.getAttribute('data-sala-id');
                    const index = parseInt(img.getAttribute('data-index'), 10);
                    openModal(salaId, index);
                });
            });

            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                    currentSalaId = null;
                    currentIndex = 0;
                    modalImg.src = '';
                }
            });

            document.addEventListener('keydown', (e) => {
                if (modal.style.display === 'flex') {
                    if (e.key === 'ArrowLeft') prevBtn.click();
                    if (e.key === 'ArrowRight') nextBtn.click();
                    if (e.key === 'Escape') closeBtn.click();
                }
            });

            // Validaciones para formularios

            document.addEventListener('DOMContentLoaded', () => {
                // Validar formulario de comentarios
                document.querySelectorAll('.form-comentario').forEach(form => {
                    form.addEventListener('submit', (e) => {
                        const textarea = form.querySelector('textarea[name="comentario"]');
                        const comentario = textarea.value.trim();

                        if (!comentario) {
                            alert('El comentario no puede estar vacío.');
                            textarea.focus();
                            e.preventDefault();
                            return false;
                        }

                        if (comentario.length < 5) {
                            alert('El comentario debe tener al menos 5 caracteres.');
                            textarea.focus();
                            e.preventDefault();
                            return false;
                        }

                        if (comentario.length > 500) {
                            alert('El comentario no puede superar los 500 caracteres.');
                            textarea.focus();
                            e.preventDefault();
                            return false;
                        }
                    });
                });

                // Confirmación para eliminar sala
                document.querySelectorAll('form[action="eliminar_sala.php"]').forEach(form => {
                    form.addEventListener('submit', (e) => {
                        if (!confirm('¿Estás seguro de que deseas eliminar esta sala?')) {
                            e.preventDefault();
                        }
                    });
                });
            });
        })();
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
