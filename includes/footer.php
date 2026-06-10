<?php
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'login.php' && $current_page !== 'register.php'): ?>
    </main>
</div>
<?php endif; ?>
</body>
</html>