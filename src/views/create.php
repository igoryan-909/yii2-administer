<?php
/**
 * @var View $this
 * @var string $title
 * @var array $breadcrumbs
 * @var array $buttons
 * @var string $form
 */

use yii\web\View;

$this->title = $title;
$this->params['breadcrumbs'] = $breadcrumbs;
?>

<div class="administer-create">
    <p class="clear">
        <?php if (isset($buttons['index'])) : ?>
            <?= $buttons['index'] ?>
        <?php endif; ?>
    </p>
    <div class="administer-form">
        <?= $form ?>
    </div>
</div>
