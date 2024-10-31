<?php use  \Premmerce\ProductBundles\ProductBundlesPlugin; ?>

<?php defined('WPINC') || die; ?>


<strong>
    <a href="<?php echo $bundleEditUrl; ?>">
        <?php echo get_the_title($item->main_product_id); ?>
    </a> <?php echo $status; ?>
</strong>

<div class="row-actions">
    <span class="edit">
        <?php if($bundleEditUrl): ?>
        <a href="<?php echo esc_url($bundleEditUrl); ?>">
            <?php _e('Edit', ProductBundlesPlugin::DOMAIN); ?>
        </a>

        <?php else: _e('Edit', ProductBundlesPlugin::DOMAIN); ?>

        <?php endif; ?>
        |

    </span>
    <span class="delete">
            <?php if($bundleDelUrl): ?>
        <a data-id="<?php echo $item->id;?>"
           data-link="<?php echo esc_attr($delType);?>"
           href="<?php echo esc_url($bundleDelUrl);?>"
        >
            <?php _e('Delete', ProductBundlesPlugin::DOMAIN); ?>
        </a>

        <?php else: _e('Delete', ProductBundlesPlugin::DOMAIN); ?>

        <?php endif; ?>
        |
    </span>
    <span class="view">
        <a
                href="<?php echo get_permalink($item->main_product_id); ?>"
                target="_blank"
        >
        <?php _e('View', ProductBundlesPlugin::DOMAIN); ?>
        </a>
    </span>
</div>