<?php

/**
 * Credit cases template.
 */
global $wpdb;

$query = "
	SELECT
		credit_case.id AS credit_case_id,
		credit_case.created_at AS credit_case_created_at,
		company.name AS company_name,
		company.post_id AS company_post_id,
		contact_post.post_title AS contact_name,
		contact_post.ID AS contact_post_id,
		SUM( credit_case_invoice.invoice_amount ) AS credit_case_amount
	FROM
		orbis_credit_cases AS credit_case
			INNER JOIN
		orbis_companies AS company
				ON credit_case.company_id = company.id
			LEFT JOIN
		$wpdb->posts AS contact_post
				ON credit_case.contact_post_id = contact_post.ID
			LEFT JOIN
		orbis_credit_case_invoices AS credit_case_invoice
				ON credit_case_invoice.credit_case_id = credit_case.id
	GROUP BY
		credit_case.id
	;
";

$data = $wpdb->get_results( $query );

get_header();

?>
<table class="table table-striped">
	<thead>
		<tr>
			<th scope="col"><?php esc_html_e( 'ID', 'orbis-credit-cases' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Date', 'orbis-credit-cases' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Company', 'orbis-credit-cases' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Contact', 'orbis-credit-cases' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Amount', 'orbis-credit-cases' ); ?></th>
		</tr>
	</thead>

	<tbody>

		<?php foreach ( $data as $item ) : ?>

			<tr>
				<?php

				$credit_case_url = \home_url( \user_trailingslashit( 'orbis-credit-cases/' . $item->credit_case_id ) );

				?>
				<td>
					<?php 

					\printf(
						'<a href="%s">%s</a>',
						\esc_url( $credit_case_url ),
						\esc_html( $item->credit_case_id )
					);

					?>
				</td>
				<td>
					<?php echo \esc_html( $item->credit_case_created_at ); ?>
				</td>
				<td>
					<?php echo \esc_html( $item->company_name ); ?>
				</td>
				<td>
					<?php echo \esc_html( $item->contact_name ); ?>
				</td>
				<td>
					<?php echo \esc_html( number_format_i18n( $item->credit_case_amount, 2 ) ); ?>
				</td>
			</tr>

		<?php endforeach; ?>

	</tbody>
</table>

<?php

get_footer();
