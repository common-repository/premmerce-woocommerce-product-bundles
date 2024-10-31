<?php
if ( ! defined('WPINC')) {
    die;
}
$singular = $table->_args['singular'];

if ( ! $isProductPage) {
    $table->display_tablenav('top');
}

$table->screen->render_screen_reader_content('heading_list');
?>

    <table class="wp-list-table <?php echo implode(' ', $table->get_table_classes()); ?>">
        <thead>
        <tr>
            <?php $table->print_column_headers(); ?>
        </tr>
        </thead>

        <tbody id="the-list" <?php if ($singular): ?> data-wp-lists='list:$singular' <?php endif; ?>>
        <?php $table->display_rows_or_placeholder(); ?>
        </tbody>

        <?php if ( ! $isProductPage): ?>
            <tfoot>
            <tr>
                <?php $table->print_column_headers(false); ?>
            </tr>
            </tfoot>
        <?php endif; ?>

    </table>

<?php
$table->display_tablenav('bottom');
?>