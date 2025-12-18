    </main> <!-- End main-wrapper from header.php -->
    
    <footer class="valley-footer">
        <div class="container d-flex flex-column align-items-center gap-3">
            <div class="footer-content">
                <p class="footer-title">
                    <?php
                    // Calculate path prefix (same logic as header.php)
                    $script_name = $_SERVER['SCRIPT_NAME'];
                    $script_dir = dirname($script_name);
                    if ($script_dir === '/') {
                        $path_prefix = '';
                    } else {
                        $path_segments = trim($script_dir, '/');
                        $segment_count = $path_segments === '' ? 0 : substr_count($path_segments, '/') + 1;
                        $path_prefix = str_repeat('../', $segment_count);
                    }
                    ?>
                    <a href="<?php echo $path_prefix; ?>index.php">Valley by Night</a>
                </p>
                <p class="footer-tagline">A Vampire Tale</p>
            </div>
            <div class="footer-bottom">
                <p class="copyright">
                    &copy; <?php echo date('Y'); ?> Valley by Night. All Rights Reserved.
                </p>
                <p class="disclaimer">
                    Based on Vampire: The Masquerade &copy; White Wolf Publishing
                </p>
            </div>
        </div>
    </footer>
</div> <!-- End page-wrapper -->
<!-- Bootstrap 5.3.2 JS Bundle - Load before closing body tag -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<!-- Modal Fullscreen Functionality -->
<script src="<?php echo $path_prefix; ?>js/modal_fullscreen.js"></script>
<?php
// Centralized JavaScript loading (similar to $extra_css in header.php)
// Pages can define $extra_js array to include page-specific JavaScript files
if (isset($extra_js) && is_array($extra_js)) {
    foreach ($extra_js as $jsPath) {
        $normalizedPath = ltrim($jsPath, '/');
        // Support defer attribute by checking if path ends with :defer
        $defer = '';
        if (strpos($normalizedPath, ':defer') !== false) {
            $normalizedPath = str_replace(':defer', '', $normalizedPath);
            $defer = ' defer';
        }
        echo '<script src="' . htmlspecialchars($path_prefix . $normalizedPath, ENT_QUOTES, 'UTF-8') . '"' . $defer . '></script>' . PHP_EOL;
    }
}
?>
</body>
</html>

