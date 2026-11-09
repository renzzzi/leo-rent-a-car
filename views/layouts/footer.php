    </main>

    <!-- Global Layout Footer -->
    <footer class="bg-white border-t border-slate-200 py-4 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-500">
            <div>
                &copy; <?= date('Y') ?> <strong class="text-slate-700">Leo Rent-A-Car</strong> Operations System. All rights reserved.
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-[3px] bg-slate-100 border border-slate-200 text-slate-600 text-[11px] font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    System Live &bull; Encrypted
                </span>
                <span>Authorized Personnel Only</span>
            </div>
        </div>
    </footer>

    <!-- View-specific script loader matching Technical Standards -->
    <?php
    $scriptName = ($pageToLoad ?? 'bookings') . 'ViewScript.js';
    $scriptPath = __DIR__ . '/../../public/assets/js/' . $scriptName;
    if (file_exists($scriptPath)):
    ?>
        <script src="assets/js/<?= htmlspecialchars($scriptName) ?>"></script>
    <?php endif; ?>

</body>
</html>
