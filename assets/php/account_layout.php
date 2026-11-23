<?php
declare(strict_types=1);

function sideb_account_identity(array $profile, string $initial, string $active): void
{
    ?>
    <aside class="profile-sidebar account-identity">
        <div class="account-identity-top">
            <div class="profile-avatar" aria-hidden="true">
                <?php if ($profile['avatar_url']): ?><img src="<?= e($profile['avatar_url']) ?>" alt=""><?php else: ?><?= e($initial) ?><?php endif; ?>
            </div>
            <div>
                <p class="account-alias"><?= e($profile['alias']) ?></p>
                <p class="account-handle">@<?= e($profile['nom_usr']) ?></p>
                <?php if (!empty($profile['fecha_creacion'])): ?>
                    <p class="account-handle">Miembro desde <?= e(date('Y', strtotime($profile['fecha_creacion']))) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?= sideb_profile_menu($active) ?>
    </aside>
    <?php
}
