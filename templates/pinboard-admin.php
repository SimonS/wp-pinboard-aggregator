<div class="wrap">
    <div class="icon32" id="icon-options-general"><br></div>
    <h2>Pinboard Aggregator Settings</h2>
    <p>Use this page to set your pinboard credentials, update schedule, etc.</p>
    <form action="options.php" method="post">
        <?php settings_fields('plugin_options'); ?>
        <?php do_settings_sections(__FILE__); ?>
        <p class="submit">
            <input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
        </p>
    </form>
</div>