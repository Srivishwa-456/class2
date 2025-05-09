    </div>

    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Attendance Management System. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo isset($is_admin) || isset($is_student) ? '../assets/js/script.js' : 'assets/js/script.js'; ?>"></script>
</body>
</html> 