<?php
/*
 * Basic Section
 */
?>

<?php if ('untappd_field-secret-key' == $field['label_for']) : ?>

    <input id="<?php esc_attr_e('untappd_settings[basic][field-secret-key]'); ?>" name="<?php esc_attr_e('untappd_settings[basic][field-secret-key]'); ?>" class="regular-text" value="<?php esc_attr_e($settings['basic']['field-secret-key']); ?>" />

<?php endif; ?>

<?php if ('untappd_field-client-key' == $field['label_for']) : ?>

    <input id="<?php esc_attr_e('untappd_settings[basic][field-client-key]'); ?>" name="<?php esc_attr_e('untappd_settings[basic][field-client-key]'); ?>" class="regular-text" value="<?php esc_attr_e($settings['basic']['field-client-key']); ?>" />

<?php endif; ?>

