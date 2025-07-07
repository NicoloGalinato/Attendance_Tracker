<!-- Add this after the password field in the login form -->
<?php if (isset($_SESSION['show_captcha'])): ?>
    <div class="mb-3">
        <label for="captcha" class="form-label">CAPTCHA</label>
        <div class="d-flex align-items-center gap-3">
            <input type="text" class="form-control" id="captcha" name="captcha" required>
            <img src="includes/captcha.php" alt="CAPTCHA" class="captcha-image" style="cursor: pointer;" onclick="this.src='includes/captcha.php?'+Math.random()">
        </div>
        <small class="text-muted">Click on the image to refresh</small>
    </div>
    <?php unset($_SESSION['show_captcha']); ?>
<?php endif; ?>