<?php

add_filter(
	'wpcf7_contact_form_properties',
	'wpcf7_constant_contact_register_property',
	10, 2
);

function wpcf7_constant_contact_register_property( $properties, $contact_form ) {
	$service = WPCF7_ConstantContact::get_instance();

	if ( ! $service->is_active() ) {
		return $properties;
	}

	if ( isset( $properties['constant_contact'] ) ) {
		return $properties;
	}

	$prop = array(
		'enable_contact_list' => false,
		'contact_lists' => array(),
	);

	$service_option = (array) WPCF7::get_option( 'constant_contact' );

	$additional_settings = $contact_form->additional_setting(
		'constant_contact',
		false
	);

	$prop['enable_contact_list'] = ! array_filter(
		$additional_settings,
		function ( $setting ) {
			return in_array( $setting, array( 'off', 'false', '0' ), true );
		}
	);

	if ( isset( $service_option['contact_lists'] ) ) {
		$contact_lists = (array) $service_option['contact_lists'];
		$contact_lists_selected = array();

		foreach ( $contact_lists as $list ) {
			if ( empty( $list['selected'] ) ) {
				continue;
			}

			foreach ( (array) $list['selected'] as $key => $val ) {
				if ( ! isset( $contact_lists_selected[$key] ) ) {
					$contact_lists_selected[$key] = array();
				}

				$contact_lists_selected[$key][] = $list['list_id'];
			}
		}

		$related_keys = array(
			sprintf( 'wpcf7_contact_form:%d', $contact_form->id() ),
			'default',
		);

		foreach ( $related_keys as $key ) {
			if ( ! empty( $contact_lists_selected[$key] ) ) {
				$prop['contact_lists'] = $contact_lists_selected[$key];
				break;
			}
		}
	}

	$properties['constant_contact'] = $prop;

	return $properties;
}


add_filter(
	'wpcf7_editor_panels',
	'wpcf7_constant_contact_editor_panels',
	10, 1
);

function wpcf7_constant_contact_editor_panels( $panels ) {
	$service = WPCF7_ConstantContact::get_instance();

	if ( ! $service->is_active() ) {
		return $panels;
	}

	$contact_form = WPCF7_ContactForm::get_current();

	$prop = $contact_form->prop( 'constant_contact' );

	$editor_panel = function () use ( $prop, $service ) {

		$description = sprintf(
			esc_html(
				__( "You can set up the Constant Contact integration here. For details, see %s.", 'contact-form-7' )
			),
			wpcf7_link(
				__( 'https://contactform7.com/constant-contact-integration/', 'contact-form-7' ),
				__( 'Constant Contact integration', 'contact-form-7' )
			)
		);

		$lists = $service->get_contact_lists();

?>
<h2><?php echo esc_html( __( 'Constant Contact', 'contact-form-7' ) ); ?></h2>

<fieldset>
	<legend><?php echo $description; ?></legend>

	<table class="form-table" role="presentation">
		<tbody>
			<tr class="<?php echo $prop['enable_contact_list'] ? '' : 'inactive'; ?>">
				<th scope="row">
		<?php

		echo esc_html( __( 'Contact lists', 'contact-form-7' ) );

		?>
				</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text">
		<?php

		echo esc_html( __( 'Contact lists', 'contact-form-7' ) );

		?>
						</legend>
						<label for="wpcf7-ctct-enable-contact-list">
							<input type="checkbox" name="wpcf7-ctct[enable_contact_list]" id="wpcf7-ctct-enable-contact-list" value="1" <?php checked( $prop['enable_contact_list'] ); ?> />
		<?php

		echo esc_html(
			__( "Add form submitters to your contact lists", 'contact-form-7' )
		);

		?>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"></th>
				<td>
					<fieldset>
		<?php

		if ( $lists ) {
			echo sprintf(
				'<legend>%1$s</legend>',
				esc_html( __( 'Select lists to which contacts are added:', 'contact-form-7' ) )
			);

			echo '<ul>';

			foreach ( $lists as $list ) {
				echo sprintf(
					'<li><label><input %1$s /> %2$s</label></li>',
					wpcf7_format_atts( array(
						'type' => 'checkbox',
						'name' => 'wpcf7-ctct[contact_lists][]',
						'value' => $list['list_id'],
						'checked' => in_array( $list['list_id'], $prop['contact_lists'] )
							? 'checked'
							: '',
					) ),
					esc_html( $list['name'] )
				);
			}

			echo '</ul>';
		} else {
			echo sprintf(
				'<legend>%1$s</legend>',
				esc_html( __( 'You have no contact list yet.', 'contact-form-7' ) )
			);
		}

		?>
					</fieldset>
		<?php

		echo sprintf(
			'<p><a %1$s>%2$s <span class="screen-reader-text">%3$s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></p>',
			wpcf7_format_atts( array(
				'href' => 'https://app.constantcontact.com/pages/contacts/ui#lists',
				'target' => '_blank',
				'rel' => 'external noreferrer noopener',
			) ),
			esc_html( __( 'Manage your contact lists', 'contact-form-7' ) ),
			esc_html( __( '(opens in a new tab)', 'contact-form-7' ) )
		);

		?>
				</td>
			</tr>
		</tbody>
	</table>
</fieldset>
<?php
	};

	$panels += array(
		'ctct-panel' => array(
			'title' => __( 'Constant Contact', 'contact-form-7' ),
			'callback' => $editor_panel,
		),
	);

	return $panels;
}