<?php

if (!defined('BIORRHYTHMS_ENTRYPOINT')) {
    http_response_code(404);
    exit;
}
?>
        <header class="site-header">
            <a class="wordmark" href="#lectura" aria-label="Biorrhythms, ir a la lectura">Biorrhythms</a>
            <?php if (!$showWelcome && !$embedMode) { require __DIR__ . '/navigation.php'; } ?>
            <?php if (!$showWelcome || $embedMode): ?>
            <button id="themeToggleBtn" type="button" class="theme-toggle">Tema oscuro</button>
            <?php endif; ?>
        </header>
