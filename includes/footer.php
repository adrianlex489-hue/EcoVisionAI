    <?php if (!isset($skip_jquery)): ?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
    <?php if (isset($js_extra)): ?>
        <script src="<?php echo BASE_URL; ?>/assets/js/<?php echo $js_extra; ?>"></script>
    <?php endif; ?>
    <?php if (isset($js_auth)): ?>
        <script src="<?php echo BASE_URL; ?>/assets/js/auth.js"></script>
    <?php endif; ?>
    <?php if (isset($js_admin)): ?>
        <script src="<?php echo BASE_URL; ?>/assets/js/admin.js"></script>
    <?php endif; ?>
    <?php if (isset($js_entrenamiento)): ?>
        <script src="<?php echo BASE_URL; ?>/assets/js/entrenamiento.js"></script>
    <?php endif; ?>

<!-- Scroll to top — fully self-contained, no external dependencies -->
<button id="scrollToTopBtn"
        aria-label="Volver arriba"
        title="Volver arriba"
        style="
            position:fixed;
            bottom:24px;
            right:24px;
            width:46px;
            height:46px;
            border-radius:50%;
            background:#198754;
            border:none;
            cursor:pointer;
            box-shadow:0 3px 10px rgba(0,0,0,0.25);
            opacity:0;
            visibility:hidden;
            transform:translateY(10px);
            transition:opacity .3s,visibility .3s,transform .3s,background .2s;
            z-index:99999;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:0;
        "
        onmouseover="this.style.background='#157347'"
        onmouseout="this.style.background='#198754'">
    <svg xmlns="http://www.w3.org/2000/svg"
         width="22" height="22"
         viewBox="0 0 24 24"
         fill="none"
         stroke="#ffffff"
         stroke-width="3"
         stroke-linecap="round"
         stroke-linejoin="round"
         style="display:block;pointer-events:none;">
        <polyline points="18 15 12 9 6 15"></polyline>
    </svg>
</button>
</body>
</html>
