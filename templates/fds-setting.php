<?php
/**
* This is the template of the shield settings page, where some settings related to the plugins are specified.
*/
?>
<div class="wrap">
<h2>FDS search settings</h2>
<form method="post" action="options.php">
<?php wp_nonce_field('update-options'); ?>
<table class="form-table">
<tr valign="top">
<th scope="row">Insert your search page URL here</th>
<td>
	<input type="url" name="fds_search_option" value="<?php echo  get_option('fds_search_option') ?>">
	</td>
</tr>
</table>
<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="fds_search_option" />
<p class="submit">
<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
</p>
</form>
</div>