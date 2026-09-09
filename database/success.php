<?php
// ============================================================
// AeroGlide — database/success.php
// Reusable Success Component & Notification Renderer
// ============================================================

if (!function_exists('render_success_banner')) {
    function render_success_banner(string $title, string $message, ?string $actionUrl = null, ?string $actionText = null): void
    {
        ?>
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-left: 5px solid #22c55e; border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; color: #14532d; font-family: 'Plus Jakarta Sans', sans-serif;">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <div style="font-size: 1.5rem; line-height: 1;">✅</div>
                <div style="flex: 1;">
                    <h4 style="margin: 0 0 4px 0; font-size: 1.05rem; font-weight: 800; color: #166534;"><?= htmlspecialchars($title) ?></h4>
                    <p style="margin: 0; font-size: 0.92rem; color: #15803d; line-height: 1.5;"><?= htmlspecialchars($message) ?></p>
                    <?php if ($actionUrl && $actionText): ?>
                        <div style="margin-top: 12px;">
                            <a href="<?= htmlspecialchars($actionUrl) ?>" style="display: inline-block; background: #166534; color: #ffffff; padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 0.85rem; text-decoration: none; transition: background 0.2s;">
                                <?= htmlspecialchars($actionText) ?> →
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('render_success_badge')) {
    function render_success_badge(string $label = 'Confirmed'): string
    {
        return '<span style="display: inline-flex; align-items: center; gap: 4px; background: #dcfce7; color: #15803d; font-weight: 700; font-size: 0.78rem; padding: 4px 10px; border-radius: 99px;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>' . htmlspecialchars($label) . '</span>';
    }
}
