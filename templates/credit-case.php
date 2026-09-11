<?php

/**
 * Credit case template.
 */
global $wpdb;

$credit_case_id = get_query_var( 'orbis_credit_case_id' );

$query = $wpdb->prepare(
	"
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
	WHERE
		credit_case.id = %d
	GROUP BY
		credit_case.id
	LIMIT
		1
	;
	",
	$credit_case_id
);

$item = $wpdb->get_row( $query );

$credit_case = $item;

$query = $wpdb->prepare(
	"
	SELECT
		*
	FROM
		orbis_credit_case_invoices AS credit_case_invoice
	WHERE
		credit_case_invoice.credit_case_id = %d
	ORDER BY
		credit_case_invoice.invoice_due_date
	;
	",
	$credit_case_id
);

$invoices = $wpdb->get_results( $query );

/**
 * Interest
 *
 * -	2019-06-21 - 2019-21-31 » 8%
 * -	2020-01-01 - 2020-06-02 » 8%
 */
$interests = array(
	'01-01-2020' => 8.00,
	'01-07-2019' => 8.00,
	'01-01-2019' => 8.00,
	'01-07-2018' => 8.00,
	'01-01-2018' => 8.00,
	'01-07-2017' => 8.00,
	'01-01-2017' => 8.00,
	'01-07-2016' => 8.00,
	'01-01-2016' => 8.05,
	'01-01-2015' => 8.05,
	'01-07-2014' => 8.15,
	'01-01-2014' => 8.25,
	'01-07-2013' => 8.50,
	'01-01-2013' => 7.75,
	'01-07-2012' => 8.00,
	'01-01-2012' => 8.00,
	'01-07-2011' => 8.25,
	'01-07-2009' => 8.00,
	'01-01-2009' => 9.50,
	'01-07-2008' => 11.07,
	'01-01-2008' => 11.20,
	'01-07-2007' => 11.07,
	'01-01-2007' => 10.58,
	'01-07-2006' => 9.83,
	'01-01-2006' => 9.25,
	'01-07-2005' => 9.05,
	'01-01-2005' => 9.09,
	'01-07-2004' => 9.01,
	'01-01-2004' => 9.02,
	'01-07-2003' => 9.10,
	'01-01-2003' => 9.85,
	'01-12-2002' => 10.35,
	'01-01-2002' => 7.00,
	'01-01-2001' => 8.00,
	'01-01-1998' => 6.00,
	'01-07-1996' => 5.00,
	'01-01-1996' => 7.00,
	'01-01-1995' => 8.00,
	'01-01-1994' => 9.00,
	'01-07-1993' => 10.00,
	'01-01-1992' => 12.00,
	'01-07-1990' => 11.00,
	'01-01-1990' => 10.00,
	'01-04-1987' => 8.00,
	'01-01-1983' => 9.00,
	'01-04-1980' => 12.00,
	'01-01-1979' => 10.00,
	'01-04-1976' => 8.00,
	'01-05-1974' => 10.00,
	'01-11-1972' => 8.00,
	'01-03-1971' => 9.00,
	'01-01-1934' => 5.00,
);

$collection_costs = array(
	1 => (object) array(
		'index'      => 1,
		'max'        => 2500,
		'percentage' => 15,
		'min_costs'  => 40,
	),
	2 => (object) array(
		'index'      => 2,
		'max'        => 2500,
		'percentage' => 10,
		'min_costs'  => null,
	),
	3 => (object) array(
		'index'      => 3,
		'max'        => 5000,
		'percentage' => 5,
		'min_costs'  => null,
	),
	4 => (object) array(
		'index'      => 4,
		'max'        => 190000,
		'percentage' => 1,
		'min_costs'  => null,
	),
	5 => (object) array(
		'index'      => 5,
		'max'        => null,
		'percentage' => 0.5,
		'min_costs'  => null,
	),
);

function orbis_credit_case_get_collection_costs( $amount, $collection_costs ) {
	$left = $amount;

	foreach ( $collection_costs as $collection_cost ) {
		$amount = $left;

		if ( null !== $collection_cost->max && $amount > $collection_cost->max ) {
			$amount = $collection_cost->max;
		}

		$collection_cost->amount = $amount;
		$collection_cost->costs  = ( $amount / 100 ) * $collection_cost->percentage;

		if ( null !== $collection_cost->min_costs ) {
			$collection_cost->costs = max( $collection_cost->costs, $collection_cost->min_costs );
		}

		$left -= $amount;

		if ( 0 === $left ) {
			return $collection_costs;
		}
	}

	return $collection_costs;
}

$collection_costs = orbis_credit_case_get_collection_costs( $item->credit_case_amount, $collection_costs );

$interest_objects = array();

foreach ( $interests as $date => $percentage ) {
	$interest_objects[ $date ] = (object) array(
		'date'       => new \DateTimeImmutable( $date ),
		'percentage' => $percentage,
	); 
}

$next = null;

foreach ( $interest_objects as $interest_object ) {
	if ( null !== $next ) {
		$next->previous = $interest_object;
		$next->previous->next = $next;
	}

	$next = $interest_object;
}

foreach ( $interest_objects as $interest_object ) {
	$interest_object->changed = true;

	if ( null !== $interest_object->previous && $interest_object->percentage === $interest_object->previous->percentage ) {
		$interest_object->changed = false;
	}
}

/*
$interest_objects = \array_filter( $interest_objects, function( $item ) {
	return $item->changed;
} );
*/

if ( filter_input( INPUT_GET, 'debug_interest' ) ) {
	echo '<pre>';
	foreach ( $interest_objects as $interest_object ) {
		echo $interest_object->date->format( 'd-m-Y' ), ' - ', $interest_object->percentage, ' - ', $interest_object->changed ? 'Yes' : 'No', PHP_EOL;
	}
	echo '</pre>';
}

function orbis_credit_case_get_interest_percentage( $interest_objects, $date ) {
	$test = \array_filter( $interest_objects, function( $item ) use ( $date ) {
		return $item->date <= $date;
	} );

	return \reset( $test );
}

$today = new \DateTimeImmutable();

$interest_rate_per_year = 8;
$interest_rate_per_day  = $interest_rate_per_year / 365;

$mutations = array();

$previous = null;

foreach ( $invoices as $invoice ) {
	if ( null !== $previous ) {
		$previous->next = $invoice;
	}

	$previous = $invoice;
}

foreach ( $invoices as $invoice ) {
	$invoice->interest_periods = array();

	$invoice_due_date = new \DateTimeImmutable( $invoice->invoice_due_date );

	$first_day_of_period = clone $invoice_due_date;
	$last_day_of_period  = $invoice_due_date->modify( '+1 year' );

	while ( $first_day_of_period < $today ) {
		$start_date = max( $invoice_due_date, $first_day_of_period );
		$end_date   = min( $today, $last_day_of_period );

		$invoice->interest_periods[] = (object) array(
			'start_date' => $start_date,
			'end_date'   => $end_date,
		);

		$first_day_of_period = $first_day_of_period->modify( '+1 year' );
		$last_day_of_period  = $last_day_of_period->modify( '+1 year' );
	}

	$amount   = $invoice->invoice_amount;
	$interest = 0;

	foreach ( $invoice->interest_periods as $period ) {
		$difference = $period->start_date->diff( $period->end_date );

		$period->amount   = $amount;
		$period->interest = ( ( $period->amount / 365 ) * $difference->days ) * ( 8 / 100 );
		$period->days     = $difference->days;

		$amount = $amount + $period->interest;
		$interest = $interest + $period->interest;
	}

	$invoice->interest = $interest;
}

get_header();

?>
<h2>
	<?php

	printf(
		__( 'Orbis Credit Case %s', 'orbis-credit-cases' ),
		\esc_html( $credit_case_id )
	);

	?>
</h2>

<table class="table table-borderless table-sm mb-0 w-auto">
	<tbody>
		<tr>
			<th scope="row"><?php \esc_html_e( 'ID', 'orbis-credit-cases' ); ?></th>
			<td><?php echo \esc_html( $item->credit_case_id ); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php \esc_html_e( 'Date', 'orbis-credit-cases' ); ?></th>
			<td>
				<?php echo \esc_html( $item->credit_case_created_at ); ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php \esc_html_e( 'Company', 'orbis-credit-cases' ); ?></th>
			<td>
				<?php echo \esc_html( $item->company_name ); ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php \esc_html_e( 'Contact', 'orbis-credit-cases' ); ?></th>
			<td>
				<?php echo \esc_html( $item->contact_name ); ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php \esc_html_e( 'Email', 'orbis-credit-cases' ); ?></th>
			<td>
				<?php

				$email_addresses = array(
					\get_post_meta( $item->company_post_id, '_orbis_email', true ),
					\get_post_meta( $item->company_post_id, '_orbis_invoice_email', true ),
					\get_post_meta( $item->contact_post_id, '_orbis_email', true ),
				);

				$email_addresses = \array_filter( $email_addresses );
				$email_addresses = \array_unique( $email_addresses );

				if ( \count( $email_addresses ) > 0 ) {
					echo '<ul>';

					foreach ( $email_addresses as $email_address ) {
						echo '<li>';

						printf(
							'<a href="%s">%s</a>',
							\esc_attr( 'mailto:' . $email_address ),
							\esc_html( $email_address )
						);

						echo '</li>';
					}

					echo '</ul>';
				}

				?>
			</td>
		</tr>
	</tbody>
</table>

<h3><?php \esc_html_e( 'Invoices', 'orbis-credit-cases' ); ?></h3>

<table class="table table-striped">
	<thead>
		<tr>
			<th scope="col"><?php esc_html_e( 'ID', 'orbis-credit-cases' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Invoice Number', 'orbis-credit-cases' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Invoice Date', 'orbis-credit-cases' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Invoice Due Date', 'orbis-credit-cases' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Amount', 'orbis-credit-cases' ); ?></th>
		</tr>
	</thead>

	<tfoot>
		<tr>
			<th scope="row" colspan="4" class="text-right"><?php esc_html_e( 'Total', 'orbis-credit-cases' ); ?>
			<td><?php echo number_format_i18n( array_sum( wp_list_pluck( $invoices, 'invoice_amount' ) ), 2 ); ?>
		</tr>
	</tfoot>

	<tbody>
		
		<?php foreach ( $invoices as $invoice ) : ?>

			<tr>
				<td>
					<?php echo \esc_html( $invoice->id ); ?>
				</td>
				<td>
					<?php echo \esc_html( $invoice->invoice_number ); ?>
				</td>
				<td>
					<?php echo \esc_html( $invoice->invoice_date ); ?>
				</td>
				<td>
					<?php echo \esc_html( $invoice->invoice_due_date ); ?>
				</td>
				<td>
					<?php echo \esc_html( number_format_i18n( $invoice->invoice_amount, 2 ) ); ?>
				</td>
			</tr>

		<?php endforeach; ?>

	</tbody>
</table>

<h3><?php \esc_html_e( 'Collection Costs', 'orbis-credit-cases' ); ?></h3>

<p>
	Voor facturen van na 1 juli 2012.
</p>

<?php

/**
 * - 15% over de eerste €2500,00
 * - 10% over de volgende €2500,00
 * - 5% over de resterende €3392,84
 */

$total_collection_costs = \array_sum( \wp_list_pluck( $collection_costs, 'costs' ) );

?>

<table class="table table-striped">
	<thead>
		<tr>
			<th scope="col">Omschrijving</th>
			<th scope="col">Incassokosten</th>
		</tr>
	</thead>

	<tfoot>
		<tr>
			<th scope="row"><?php esc_html_e( 'Total', 'orbis-credit-cases' ); ?></th>
			<td><?php echo number_format_i18n( $total_collection_costs, 2 ); ?></td>
		</tr>
	</tfoot>

	<tbody>

		<?php foreach ( $collection_costs as $collection_cost ) : ?>

			<tr>
				<td>
					<?php 

					if ( 1 === $collection_cost->index && $collection_cost->amount === $collection_cost->max ) {
						printf( 
							'%s on the first %s',
							$collection_cost->percentage . '%',
							number_format_i18n( $collection_cost->amount, 2 )
						);
					} elseif ( 1 === $collection_cost->index && $collection_cost->amount < $collection_cost->max ) {
						printf(
							'%s on %s',
							$collection_cost->percentage . '%',
							number_format_i18n( $collection_cost->amount, 2 )
						);
					} elseif ( $collection_cost->amount === $collection_cost->max ) {
						printf( 
							'%s on the next %s',
							$collection_cost->percentage . '%',
							number_format_i18n( $collection_cost->amount, 2 )
						);
					} elseif ( $collection_cost->amount < $collection_cost->max ) {
						printf(
							'%s on the remaining %s',
							$collection_cost->percentage . '%',
							number_format_i18n( $collection_cost->amount, 2 )
						);
					} else {
						printf(
							'%s on the remaining %s',
							$collection_cost->percentage . '%',
							number_format_i18n( $collection_cost->amount, 2 )
						);
					}

					?>
				</td>
				<td>
					<?php echo esc_html( number_format_i18n( $collection_cost->costs, 2 ) ); ?>
				</td>
			</tr>

		<?php endforeach; ?>

	</tbody>
</table>

<h3><?php \esc_html_e( 'Interest', 'orbis-credit-cases' ); ?></h3>

<?php 

$n = 1;

$rows = array();

$first_invoice = \reset( $invoices );

$first_date = new \DateTimeImmutable( $first_invoice->invoice_due_date );

$last_date = new \DateTimeImmutable();

$today = new \DateTimeImmutable();

$rente_dagen = array_filter( $interest_objects, function( $item ) use ( $first_date, $last_date ) {
	return $item->date >= $first_date && $item->date < $last_date;
} );

foreach ( $rente_dagen as $item ) {
	$key = $item->date->format( 'Y-m-d' );

	if ( ! array_key_exists( $key, $rows ) ) {
		$rows[ $key ] = (object) array(
			'date'    => $item->date,
		);
	}

	$rows[ $key ]->new_rate = $item;
}

foreach ( $invoices as $invoice ) {
	$date = new \DateTimeImmutable( $invoice->invoice_due_date );

	$key = $date->format( 'Y-m-d' );

	if ( ! array_key_exists( $key, $rows ) ) {
		$rows[ $key ] = (object) array(
			'date'    => $date,
		);
	}

	$rows[ $key ]->invoice = $invoice;
}

$period = new \DatePeriod( $first_date, new DateInterval( 'P1Y' ), $last_date, \DatePeriod::EXCLUDE_START_DATE );

foreach ( $period as $date ) {
	$key = $date->format( 'Y-m-d' );

	if ( ! array_key_exists( $key, $rows ) ) {
		$rows[ $key ] = (object) array(
			'date'    => $date,
		);
	}

	$rows[ $key ]->new_period = true;
}

usort( $rows, function( $a, $b ) {
	if ( $a->date == $b->date ) {
		return 0;
	}

	return ( $a->date < $b->date ) ? -1 : 1;
} );

$previous = null;

foreach ( $rows as $item ) {
	if ( null !== $previous ) {
		$previous->next = $item;
	}

	$previous = $item;
}

foreach ( $rows as $item ) {
	$end_date = $last_date;

	if ( null !== $item->next ) {
		$end_date = $item->next->date;
	}

	$item->start_date = $item->date;
	$item->end_date   = $end_date;
}

$amount   = 0;
$interest = 0;
$rollup   = 0;

foreach ( $rows as $item ) {
	if ( $item->new_period || $item->invoice ) {
		$amount = $amount + $interest;

		$interest = 0;
	}

	if ( $item->invoice ) {
		$amount = $amount + $item->invoice->invoice_amount;
		$rollup = $rollup + $item->invoice->invoice_amount;
	}

	$first_day_of_year = $item->start_date->modify( 'midnight first day of january this year' );
	$last_day_of_year  = $item->start_date->modify( 'midnight last day of december this year' );

	$item->total_days = $last_day_of_year->format( 'z' ) + 1;

	$difference = $item->end_date->diff( $item->start_date );

	$item->days = $difference->days;

	$interest_object = \orbis_credit_case_get_interest_percentage( $interest_objects, $item->start_date );

	$period_amount = ( $amount / $item->total_days ) * $item->days;

	$item->amount   =  $amount;
	$item->interest = ( $period_amount / 100 ) * $interest_object->percentage;

	$rollup = $rollup + $item->interest;

	$item->rollup = $rollup;

	$interest += $item->interest;
}

$total = $amount + $interest;

$total_interest = array_sum( \wp_list_pluck( $rows, 'interest' ) );

?>

<table class="table table-striped">
	<thead>
		<tr>
			<th scope="col" colspan="2"><?php esc_html_e( 'Period', 'orbis-credit-cases' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Days', 'orbis-credit-cases' ); ?></th>
			<th scope="col" colspan="2"><?php esc_html_e( 'On Amount', 'orbis-credit-cases' ); ?></th>
			<th scope="col"><?php esc_html_e( '%', 'orbis-credit-cases' ); ?></th>
			<th scope="col" colspan="2"><?php esc_html_e( 'Rente', 'orbis-credit-cases' ); ?></th>
			<th scope="col" colspan="2"><?php esc_html_e( 'Bedrag', 'orbis-credit-cases' ); ?></th>
		</tr>
	</thead>

	<tfoot>
		<tr>
			<th scope="row" colspan="6" class="text-right"><?php esc_html_e( 'Totals', 'orbis-credit-cases' ); ?></th>
			<th scope="row">€</th>
			<td><?php echo esc_html( \number_format_i18n( $total_interest, 2 ) ); ?></td>
			<th scope="row">€</th>
			<td><?php echo esc_html( \number_format_i18n( $total, 2 ) ); ?></td>
		</tr>
	</tfoot>

	<tbody>

		<?php foreach ( $rows as $item ) : ?>

			<?php if ( $item->invoice ) : ?>

				<tr>
					<td>M</td>
					<td>
						<?php echo \esc_html( $item->date->format( 'd-m-Y' ) ); ?>
					</td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td>€</td>
					<td>
						<?php

						\printf(
							'%s%s',
							$item->invoice->invoice_amount < 0 ? '-' : '+',
							\number_format_i18n( $item->invoice->invoice_amount, 2 )
						);
						
						?>
					</td>
					<td>€</td>
					<td>
						<?php echo \esc_html( \number_format_i18n( $item->amount, 2 ) ); ?>
					</td>
				</tr>

			<?php endif; ?>

			<tr>
				<td>
					<?php echo \esc_html( $n++ ); ?>
				</td>
				<td>
					<?php echo \esc_html( $item->start_date->format( 'd-m-Y' ) ); ?>
					-
					<?php echo \esc_html( $item->end_date->format( 'd-m-Y' ) ); ?>
				</td>
				<td>
					<?php 

					\printf(
						'%s / %s',
						\esc_html( $item->days ),
						\esc_html( $item->total_days )
					);

					?>
				</td>
				<td>€</td>
				<td>
					<?php echo \esc_html( number_format_i18n( $item->amount, 2 ) ); ?>
				</td>
				<td>
					<?php 

					\printf(
						'%s %%',
						\esc_html( number_format_i18n( $interest_object->percentage, 2 ) )
					);

					?>
				</td>
				<td>€</td>
				<td>
					<?php echo \esc_html( number_format_i18n( $item->interest, 2 ) ); ?>
				</td>
				<td>€</td>
				<td>
					<?php echo \esc_html( \number_format_i18n( $item->rollup, 2 ) ); ?>
				</td>
			</tr>

		<?php endforeach; ?>

	</tbody>
</table>

<div class="card mb-4">
	<div class="card-header">
		Herinnering openstaande facturen
	</div>

	<div class="card-body">
		<table>
			<tr>
				<th scope="row" align="left" style="text-align: left;">Datum</th>
				<td><?php

				$credit_case_date = new \DateTimeImmutable( $credit_case->credit_case_created_at );

				echo \esc_html( $credit_case_date->format( 'd-m-Y' ) );

				?></td>
			</tr>
			<tr>
				<th scope="row" align="left" style="text-align: left;">Referentie</th>
				<td><?php 

				$twinfield_customer_id = get_post_meta( $credit_case->company_post_id, '_twinfield_customer_id', true );

				echo \esc_html( $twinfield_customer_id );
				echo ' ';
				echo \esc_html( $credit_case->credit_case_id );

				?></td>
			</tr>
			<tr>
				<th scope="row" align="left" style="text-align: left;">Betreft</th>
				<td>Openstaande Pronamic facturen (<?php echo \implode( ', ', \wp_list_pluck( $invoices, 'invoice_number' ) ); ?>)</td>
			</tr>
		</table>
		<br>
		Hoi %,<br>
		<br>
		Hoe gaat het bij jullie? Houden jullie het hoofd nog boven water ondanks de coronacrisis?
		Wij beginnen de impact van de coronacrisis wel steeds meer te merken.<br>
		<br>
		In ieder geval hebben we wel wat tijd gevonden voor enkele administratieve werkzaamheden. Daarbij 
		vielen ons een aantal openstaande posten op, kan het kloppen dat de volgende facturen nog 
		niet betaald zijn?<br>
		<br>
		<table width="100%">
			<thead>
				<tr>
					<th scope="scope" align="left" style="text-align: left;">Factuurnummer</th>
					<th scope="scope" align="left" style="text-align: left;">Factuurdatum</th>
					<th scope="scope" align="left" style="text-align: left;">Vervaldatum</th>
					<th scope="scope" align="left" style="text-align: left;" colspan="2">Bedrag</th>
				</tr>
			</thead>

			<tfoot>
				<tr>
					<th colspan="3" scope="row" align="left" style="text-align: left;">Totaal verschuldigd</td>
					<td style="border-top: 1px solid #000;">€</td>
					<td style="border-top: 1px solid #000; text-align: right;" align="right" ><?php echo number_format_i18n( array_sum( wp_list_pluck( $invoices, 'invoice_amount' ) ), 2 ); ?></td>
				</tr>
			</tfoot>

			<tbody>
				<?php foreach ( $invoices as $invoice ) : ?>
					<tr>
						<?php

						$invoice_date     = new \DateTimeImmutable( $invoice->invoice_date );
						$invoice_due_date = new \DateTimeImmutable( $invoice->invoice_due_date );

						?>
						<td>
							<?php echo \esc_html( $invoice->invoice_number ); ?>
						</td>
						<td>
							<?php echo \esc_html( $invoice_date->format( 'd-m-Y' ) ); ?>
						</td>
						<td>
							<?php echo \esc_html( $invoice_due_date->format( 'd-m-Y' ) ); ?>
						</td>
						<td>€</td>
						<td align="right" style="text-align: right;">
							<?php echo \esc_html( number_format_i18n( $invoice->invoice_amount, 2 ) ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<br>
		Zou je dit willen nakijken en indien nodig de betaling in orde willen maken? Alvast bedankt en we horen graag van je!<br>
		<br>
		Met vriendelijke groet,<br>
		<br>
		Remco Tolsma<br>
		Pronamic
	</div>
</div>

<div class="card mb-4">
	<div class="card-header">
		Laatste herinnering / kosteloze aanmaning
	</div>

	<div class="card-body">
		<table>
			<tr>
				<th scope="row" align="left" style="text-align: left;">Datum</th>
				<td><?php

				$credit_case_date = new \DateTimeImmutable( $credit_case->credit_case_created_at );

				echo \esc_html( $credit_case_date->format( 'd-m-Y' ) );

				?></td>
			</tr>
			<tr>
				<th scope="row" align="left" style="text-align: left;">Referentie</th>
				<td><?php 

				$twinfield_customer_id = get_post_meta( $credit_case->company_post_id, '_twinfield_customer_id', true );

				echo \esc_html( $twinfield_customer_id );
				echo ' ';
				echo \esc_html( $credit_case->credit_case_id );
				echo ' 0';

				?></td>
			</tr>
			<tr>
				<th scope="row" align="left" style="text-align: left;">Betreft</th>
				<td>Laatste herinnering / kosteloze aanmaning</td>
			</tr>
		</table>
		<br>
		Geachte heer %,<br>
		<br>
		Uit onze administratie blijkt dat u onderstaande vordering nog niet heeft voldaan. U bent op dit 
		moment met betaling in verzuim.<br>
		<br>
		Het is mogelijk dat dit bericht uw betaling gekruist heeft. In dat geval bieden wij u onze
		verontschuldigingen aan en mag u dit bericht als niet verzonden beschouwen. Indien u nog niet
		betaald heeft, bieden wij u alsnog de gelegenheid om aan uw verplichting te voldoen.<br>
		<br>
		Wij stellen u nog 14 dagen in de gelegenheid om het verschuldigde bedrag te betalen op
		bankrekening NL56RABO0108634779 t.n.v. Pronamic. De termijn van 14 dagen vangt aan de dag na
		bezorging van deze laatste herinnering. De specificatie van de vordering is als volgt:<br>
		<br>
		<table width="100%">
			<thead>
				<tr>
					<th scope="scope" align="left" style="text-align: left;">Factuurnummer</th>
					<th scope="scope" align="left" style="text-align: left;">Factuurdatum</th>
					<th scope="scope" align="left" style="text-align: left;">Vervaldatum</th>
					<th scope="scope" align="left" style="text-align: left;" colspan="2">Bedrag</th>
				</tr>
			</thead>

			<tfoot>
				<tr>
					<th colspan="3" scope="row" align="left" style="text-align: left;">Totaal verschuldigd</td>
					<td style="border-top: 1px solid #000;">€</td>
					<td style="border-top: 1px solid #000; text-align: right;" align="right" ><?php echo number_format_i18n( array_sum( wp_list_pluck( $invoices, 'invoice_amount' ) ), 2 ); ?></td>
				</tr>
			</tfoot>

			<tbody>
				<?php foreach ( $invoices as $invoice ) : ?>
					<tr>
						<?php

						$invoice_date     = new \DateTimeImmutable( $invoice->invoice_date );
						$invoice_due_date = new \DateTimeImmutable( $invoice->invoice_due_date );

						?>
						<td>
							<?php echo \esc_html( $invoice->invoice_number ); ?>
						</td>
						<td>
							<?php echo \esc_html( $invoice_date->format( 'd-m-Y' ) ); ?>
						</td>
						<td>
							<?php echo \esc_html( $invoice_due_date->format( 'd-m-Y' ) ); ?>
						</td>
						<td>€</td>
						<td align="right" style="text-align: right;">
							<?php echo \esc_html( number_format_i18n( $invoice->invoice_amount, 2 ) ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<br>
		Na de eerdergenoemde 14 dagen zal de vordering als incasso behandeld worden en maken wij
		aanspraak op vergoeding van kosten. Naast bovengenoemde hoofdsom bent u dan incassokosten
		verschuldigd. De hiermee gemoeide wettelijke incassokosten bedragen €&nbsp;<?php echo number_format_i18n( $total_collection_costs, 2 ); ?>
		<br>
		Wij hopen dat u voor een tijdige betaling zorgt en dat daarmee de incassomaatregelen achterwege
		kunnen blijven.<br>
		<br>
		Met vriendelijke groet,<br>
		<br>
		<br>
		Pronamic
	</div>
</div>

<?php

get_footer();
