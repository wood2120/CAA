    </main>
    
    <?php if (isLoggedIn()): ?>
    <!-- Footer -->
    <footer class="bg-light text-center text-muted py-3 mt-5">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos los derechos reservados.</p>
            <small>Usuario: <?php echo $_SESSION['username']; ?> | Rol: <?php echo $_SESSION['user_role']; ?></small>
        </div>
    </footer>
    <?php endif; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <script>
        // Verificar timeout de sesión cada 5 minutos
        setInterval(function() {
            fetch('<?php echo SITE_URL; ?>/controllers/check_session.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.valid) {
                        alert('Su sesión ha expirado. Será redirigido al login.');
                        window.location.href = '<?php echo SITE_URL; ?>/login.php';
                    }
                });
        }, 300000); // 5 minutos
    </script>
</body>
</html>
