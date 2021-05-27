<!DOCTYPE html>
<html>
<head></head>

<body>

<script type="text/javascript">
		function checkO2OChannel() {
				var ischeck = ValidateO2OInputs();
				if (ischeck) {
						return true;
				} else {
						alert("Please choose Payment Channel to use!");
						console.log('error');
						return false;
				}
		}
		const jokulo2oQueryString = window.location.search;
		const jokulo2oUrlParams = new URLSearchParams(jokulo2oQueryString);
		const jokulo2ErrorMessage = jokulo2oUrlParams.get('jokulo2ErrorMessage')

		if(jokulo2ErrorMessage!=null){
			alert("Failed to Checkout: " + jokulo2ErrorMessage + ", Please contact our support team");
		}

		
		function ValidateO2OInputs() {
				var x = false;
				if(document.formJokulo2oOrder.PAYMENTCHANNEL.value != ''){
					x = true;
				}
				return x;
		}
		function channelO2O(paymentChannel){
			console.log(paymentChannel)
		
				document.formJokulo2oOrder.action = '{$urls.base_url}/modules/jokulo2o/serviceO2o.php';
				document.formJokulo2oOrder.CUSTOMERID.value = '{$URL_MERCHANTHOSTED}';
				document.formJokulo2oOrder.JOKULO2O_FOOTER_MESSAGE.value = '{$JOKULO2O_FOOTER_MESSAGE}';
				document.formJokulo2oOrder.EXP_TIME.value = '{$JOKULO2O_EXPIRY_TIME}';
				document.formJokulo2oOrder.PAYMENTCHANNEL.value = paymentChannel;	
		}
		
</script>

<style>
.doku_payment_module {
    display: block;
	background-color: #FBFBFB;
    border: 1px solid #D6D4D4;
    border-radius: 4px;
    line-height: 23px;
    color: #333;
    padding: 10px 0px 20px 20px;
	margin-bottom: 10px;		
}

.doku_payment_module td {
		line-height: 15px;
}

.doku_payment_module_submit {
    border-radius: 10px;
    color: white;
    background-color: #c6122f;
}
</style>

<div class="doku_payment_module">
<form name="formJokulo2oOrder" id="formJokulo2oOrder" action="{$URL}" method="post" enctype="multipart/form-data" >
		<table cellpadding="0" cellspacing="0" border="0" width="400">
			<tr>
				<td><p style="font-size:16px; font-weight:normal; text-align:justify">Choose Convenience Store you wish to pay from</p></td>
			</tr>
		</table>
		
		<li style="list-style-type: none;">										

		{if $JOKULO2O_PAYMENT_CHANNELS_ALFA}
			<ul><input type="radio" name="PAYMENTCHANNEL" value="ALFA" onclick="return channelO2O('ALFA')"> Alfamart</ul>
		{/if}

		</li>
		
		<input type="submit" class="btn btn-primary" value="PAY" onclick="return checkO2OChannel();">
		
		<input type=hidden name="REGID"  		   value="{$REGID}">
		<input type=hidden name="DATETIME"  	   value="{$DATETIME}">
    	<input type=hidden name="invoice_number"   value="{$invoice_number}">
		<input type=hidden name="order_id"   	   value="{$order_id}">
    	<input type=hidden name="amount"           value="{$amount}">
    	<input type=hidden name="REQUESTDATETIME"  value="{$REQUESTDATETIME}">
    	<input type=hidden name="CURRENCY"         value="{$CURRENCY}">
    	<input type=hidden name="PURCHASECURRENCY" value="{$PURCHASECURRENCY}">				
    	<input type=hidden name="NAME"             value="{$NAME}">
		<input type=hidden name="EMAIL"            value="{$EMAIL}">		
    	<input type=hidden name="HOMEPHONE"        value="{$HOMEPHONE}">
    	<input type=hidden name="MOBILEPHONE"      value="{$MOBILEPHONE}"> 
    	<input type=hidden name="BASKET"           value="{$BASKET}">				
    	<input type=hidden name="ADDRESS"          value="{$ADDRESS}"> 
		<input type=hidden name="JOKULO2O_FOOTER_MESSAGE"   value="{$JOKULO2O_FOOTER_MESSAGE}"> 
		<input type=hidden name="EXP_TIME"         value=""> 
		<input type=hidden name="CITY"             value="{$CITY}"> 
    	<input type=hidden name="STATE"            value="{$STATE}"> 
    	<input type=hidden name="ZIPCODE"          value="{$ZIPCODE}"> 				
    	<input type=hidden name="SHIPPING_COUNTRY" value="{$SHIPPING_COUNTRY}"> 
    	<input type=hidden name="CUSTOMERID" 	   value="{$EMAIL}"> 
		<input type=hidden name="SHIPPING_ADDRESS" value="{$SHIPPING_ADDRESS}"> 
    	<input type=hidden name="SHIPPING_CITY"    value="{$SHIPPING_CITY}">
    	<input type=hidden name="SHIPPING_ZIPCODE" value="{$SHIPPING_ZIPCODE}"> 				

</form>
</div>
</body>
</html>
