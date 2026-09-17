<?php
// Includes/footer.php
?>
    </main>
    <footer class="main-footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> Sistema de Proyectos. Todos los derechos reservados.</p>
            <p class="footer-version">Versión 1.0</p>
        </div>
    </footer>
    
    <script>
    // Cambio de idioma
    document.addEventListener('DOMContentLoaded', function() {
        const idiomaSelect = document.getElementById('cambiar_idioma');
        if (idiomaSelect) {
            idiomaSelect.addEventListener('change', function() {
                cambiarIdioma(this.value);
            });
        }
    });
    
    function cambiarIdioma(idioma) {
        const formData = new FormData();
        formData.append('idioma', idioma);
        
        fetch(window.BASE_URL + 'modules/usuarios/cambiar_idioma.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al cambiar idioma: ' + (data.error || 'Desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    </script>
</body>
</html>