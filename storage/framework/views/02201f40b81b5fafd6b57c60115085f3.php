<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        <?php
            $owner = \App\Models\Owner::first();
            $appName = $owner->name ?? config('app.name');
            $appLogo = $owner && $owner->logo && file_exists(public_path('storage/' . $owner->logo))
                ? asset('storage/' . $owner->logo)
                : asset('logo.png');
            $appEmail = $owner->email ?? 'support@' . request()->getHost();
        ?>

        <title inertia><?php echo e($appName); ?></title>
        <link rel="icon" href="<?php echo e($appLogo); ?>" />
        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

        <link rel="stylesheet" href="<?php echo e(asset('css/tailadmin.css')); ?>">
        
        <script>
            window.appConfig = {
                appName: <?php echo json_encode($appName); ?>,
                appLogo: <?php echo json_encode($appLogo); ?>,
                appEmail: <?php echo json_encode($appEmail); ?>

            };
        </script>

        <!-- Scripts -->
        <?php
            $manifestPath = public_path('build/manifest.json');
            $manifest = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [];
        ?>

        <?php if(file_exists(public_path('hot'))): ?>
            <?php
                $viteUrl = file_get_contents(public_path('hot'));
                $viteUrl = str_starts_with($viteUrl, 'http') ? rtrim($viteUrl) : 'http://localhost:5174';
            ?>
            <script type="module" src="<?php echo e($viteUrl); ?>/@vite/client"></script>
            <script type="module" src="<?php echo e($viteUrl); ?>/resources/js/main.js"></script>
        <?php else: ?>
            <?php if(isset($manifest['resources/js/main.js'])): ?>
                <?php $__currentLoopData = $manifest['resources/js/main.js']['css'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $css): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <link rel="stylesheet" href="<?php echo e(asset('build/' . $css)); ?>">
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <script type="module" src="<?php echo e(asset('build/' . $manifest['resources/js/main.js']['file'])); ?>"></script>
            <?php endif; ?>
        <?php endif; ?>
    </head>
    <body class="font-sans antialiased">
        <div id="app"></div>
    </body>
</html>
<?php /**PATH D:\SHODIQ SOLUTIN\PT.DUMAI\Manajement Barang\resources\views/app.blade.php ENDPATH**/ ?>