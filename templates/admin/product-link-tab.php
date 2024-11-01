<div id="product_link_tab" class="panel woocommerce_options_panel" style="display: none;">
	<div class='options_group'>
		<p class="form-field">
			<label for="productLinks"><?php echo __( 'Link', 'woocommerce' ) ?>:</label>
			<input type='text' name="product_link" id="productLinks" rows="10" value='<?php echo $linksForSold; ?>'>
		</p>
		<p class="form-field">
			<label for="productLinks"><?php echo __( 'Open in new tab?', 'woocommerce' ) ?></label>
			<input type="checkbox" name="new_tab" value="1" <?php checked(1, $new_tab , true); ?> />
		</p>
	</div>
</div>