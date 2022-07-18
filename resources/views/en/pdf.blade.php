<!DOCTYPE HTML>
<html>

<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Dropforcoin</title>
	<meta name="created" content="00:00:00" />
</head>

<body style="margin: 0;">
	<table cellspacing="0" style="font-family:arial; width:100%; color:#777777; padding: 20px;">
		<tbody>

			<tr>
				<td colspan="5" style="padding: 15px 0px;vertical-align: top;">
					<img src="<?php echo URL::to('/'); ?>/images/logo.png" alt="Logo" align="left" valign=bottom style="max-width:250px;">
				</td>
				</td>
				<td colspan="3" style="padding: 15px 0px;vertical-align: top;border-left: 0;">
					<h3 align="right" valign=bottom style="margin: 0 0 7px 0;color: #000;"><b>INVOICE</b></h3>
					<p align="right" valign=bottom style="margin: 7px 0;">{{ date('d/m/Y', strtotime($show->invoice_date)) }}</p>
				</td>
			</tr>
			<tr>
				<td colspan="4" style="padding: 15px 20px 0 0;vertical-align: middle; width:40%; ">
					<p align="left" valign=bottom style="margin: 7px 0;color:#333; white-space: pre;">Drop4Coin Spain</p>
				</td>
				<td colspan="4" align="right" style="padding: 15px 0px 0 30px;vertical-align: top; width:30%;">
					<h3 align="left" valign=bottom style="margin: 0 0 7px 0;color: #000;white-space: pre;"><b>Billing Address</b></h3>
					<p align="left" valign=bottom style="margin: 7px 0; color:#333;">
						<!--<span style="display:inline-block; width:100%;margin: 3px 0;">{{$show->company_name}}</span><br> -->
						<span style="display:inline-block; width:100%;margin: 3px 0;">Snisko Entertainment S.L</span><br>
						<span style="display:inline-block; width:100%;margin: 3px 0;">NIF: B42665992</span><br>
						<span style="display:inline-block; width:100%;margin: 3px 0;">Avenida Costa Blanca nº16 B1 <br>Esc1 3ºA (Alicante/Alicante) CP: 03520</span>

						<!--<span style="display:inline-block; width:100%;margin: 3px 0;">{{$show->phone}}</span>-->
						<!-- <span style="display:inline-block; width:100%;margin: 3px 0;">Virginia Biencinto Gonzalez</span> 
						<span style="display:inline-block; width:100%;margin: 3px 0;">Calle Carretera 13</span>
						<span style="display:inline-block; width:100%;margin: 3px 0;">45575 Aldeanueva de San</span>
						<span style="display:inline-block; width:100%;margin: 3px 0;">Bartolome</span>
						<span style="display:inline-block; width:100%;margin: 3px 0;">España</span>
						<span style="display:inline-block; width:100%;margin: 3px 0;">698901358</span> -->
					</p>
				</td>
			</tr>
			<tr>
				<td style="padding:10px 0 0;"></td>
			</tr>
			<tr>
				<td colspan="2" align="center" valign=bottom bgcolor="#cccccc" style="padding: 5px; border: 1px solid #bbb;border-right: none; white-space: pre;color: #000;"><b>Invoice number</b></td>
				<td colspan="2" align="center" valign=bottom bgcolor="#cccccc" style="padding: 5px; border: 1px solid #bbb; border-right: none; white-space: pre; color: #000;"><b>Invoice date</b></td>
				<td colspan="2" align="center" valign=bottom bgcolor="#cccccc" style="padding: 5px; border: 1px solid #bbb;border-right: none; white-space: pre; color: #000;"><b>Reference ID </b></td>
				<td colspan="2" align="center" valign=bottom bgcolor="#cccccc" style="padding: 5px; border: 1px solid #bbb; white-space: pre; color: #000;"><b>Order date</b></td>
			</tr>
			<!-- <tr>
				<td colspan="2" align="center" valign=bottom bgcolor="#ffffff"style="padding: 5px; border: 1px solid #bbb; border-right: none; border-top: none; white-space: pre;"></td>
				<td colspan="2" align="center" valign=bottom bgcolor="#ffffff"style="padding: 5px; border: 1px solid #bbb; border-right: none; border-top: none; white-space: pre;"></td>
				<td colspan="2" align="center" valign=bottom bgcolor="#ffffff"style="padding: 5px; border: 1px solid #bbb; border-right: none; border-top: none; white-space: pre;"></td>
				<td colspan="2" align="center" valign=bottom bgcolor="#ffffff"style="padding: 5px; border: 1px solid #bbb; border-top: none; white-space: pre; color: #000;"></td>
			</tr> -->
			<tr>
				<td colspan="2" align="center" valign=bottom bgcolor="#ffffff" style="padding: 5px;border-left: 1px solid #bbb;white-space: pre;">{{$show->invoice_id}}</td>
				<td colspan="2" align="center" valign=bottom bgcolor="#ffffff" style="padding: 5px;border-left: 1px solid #bbb;white-space: pre;">{{ date('d/m/Y', strtotime($show->invoice_date)) }}</td>
				<td colspan="2" align="center" valign=bottom bgcolor="#ffffff" style="padding: 5px;border-left: 1px solid #bbb;white-space: pre;">{{$show->payment_id}}</td>
				<td colspan="2" align="center" valign=bottom bgcolor="#ffffff" style="padding: 5px;border-left: 1px solid #bbb;border-right: 1px solid #bbb;white-space: pre;color: #000;">{{ date('d/m/Y', strtotime($show->invoice_date)) }}</td>
			</tr>
			<tr>
				<td style="padding:20px 0 0;"></td>
			</tr>
			<tr>
				<td colspan="5" align="left" valign=bottom bgcolor="#cccccc" style="padding: 5px; border: 1px solid #bbb;border-right: none; white-space: pre;color: #000;"><b>Product</b></td>
				<td colspan="3" align="right" valign=bottom bgcolor="#cccccc" style="padding: 5px; border: 1px solid #bbb; white-space: pre; color: #000;"><b>Total</b></td>
			</tr>
			<tr>
				<td colspan="5" align="left" valign=bottom bgcolor="#ffffff" style="padding: 5px; border: 1px solid #bbb; border-right: none; border-top: none; color: #333;">
					<p style="margin: 0; display: inline-block; width: 100%;"><b>@if ($show->campaign_type != "") @foreach(explode('_', $show->campaign_type) as $info) {{$info}} @endforeach @endif-</b> {{ $show->campaign_name }}</p>
					<span>CAC is {{ $show->cac }} and Campaign objectives is {{ $show->user_target }} </span>
				</td>
				<td colspan="3" align="right" valign=bottom bgcolor="#ffffff" style="padding: 5px; border: 1px solid #bbb; border-top: none; white-space: pre; color: #000;">{{ $show->sub_total }} &#128;</td>
			</tr>
			<tr>
				<td colspan="5" align="left" valign=bottom bgcolor="#fff" style="padding: 5px; white-space: pre;"></td>
				<td colspan="2" align="left" valign=bottom bgcolor="#eee" style="padding: 5px; border: 1px solid #bbb; border-right: none; border-top: none; white-space: pre; color:#000;">Amount</td>
				<td colspan="1" align="right" valign=bottom style="padding: 5px; border: 1px solid #bbb; border-top: none; white-space: pre; color: #000;">{{ $show->sub_total }} &#128;</td>
			</tr>
			<tr>
				<td colspan="5" align="left" valign=bottom bgcolor="#ffffff" style="padding: 5px; border: none; white-space: pre;"></td>
				<td colspan="2" align="left" valign=bottom bgcolor="#eee" style="padding: 5px; border: 1px solid #bbb; border-right: none; border-top: none; white-space: pre; color:#000;">Tax </td>
				<td colspan="1" align="right" valign=bottom style="padding: 5px; border: 1px solid #bbb; border-top: none; white-space: pre; color: #000;">{{ $show->tax_value }} &#128;</td>
			</tr>
			<!-- <tr>
				<td colspan="5" align="left" valign=bottom bgcolor="#ffffff"style="padding: 5px; border: none; white-space: pre;"></td>
				<td colspan="2" align="left" valign=bottom bgcolor="#eee"style="padding: 5px; border: 1px solid #bbb; border-right: none; border-top: none; white-space: pre; color:#000;font-weight: 600;">Total (Imp. excl.)</td>
				<td colspan="1" align="right" valign=bottom style="padding: 5px; border: 1px solid #bbb; border-top: none; white-space: pre; color: #000; font-weight: 600;">25,00 &#128;</td>
			</tr> -->
			<tr>
				<td colspan="5" align="left" valign=bottom bgcolor="#ffffff" style="padding: 5px; border: none; white-space: pre;"></td>
				<td colspan="2" align="left" valign=bottom bgcolor="#eee" style="padding: 5px; border: 1px solid #bbb; border-right: none; border-top: none; white-space: pre; color:#000;font-weight: 900;"><b>Total</b></td>
				<td colspan="1" align="right" valign=bottom style="padding: 5px; border: 1px solid #bbb; border-top: none; white-space: pre; color: #000; font-weight: 900;"><b>{{ $show->grand_total }} &#128;</b></td>
			</tr>
	</table>
</body>

</html>