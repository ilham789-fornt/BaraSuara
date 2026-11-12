<?php
/** Menampilkan flash message (sekali tampil). */
$flashes = pull_flashes();
if ($flashes): ?>
    <div class="flash-stack" role="status" aria-live="polite">
        <?php foreach ($flashes as $flashItem): ?>
            <div class="flash flash-<?= e($flashItem['type']) ?>">
                <i class="fas <?= $flashItem['type'] === 'success' ? 'fa-check-circle' : ($flashItem['type'] === 'error' ? 'fa-exclamation-triangle' : 'fa-info-circle') ?>"
                    aria-hidden="true"></i>
                <span><?= e($flashItem['message']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

