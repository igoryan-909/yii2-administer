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

<div class="administer-update">
    <p class="clear">
        <?php if (isset($buttons['view'])) : ?>
            <?= $buttons['view'] ?>
        <?php endif; ?>
        <?php if (isset($buttons['delete'])) : ?>
            <?= $buttons['delete'] ?>
        <?php endif; ?>
        <?php if (isset($buttons['index'])) : ?>
            <?= $buttons['index'] ?>
        <?php endif; ?>
        <?php if (isset($buttons['create'])) : ?>
            <?= $buttons['create'] ?>
        <?php endif; ?>
    </p>
    <div class="administer-form">
        <?= $form ?>
    </div>
</div>
