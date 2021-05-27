<br />
<br />
<p>
    {l s='Your order on' mod='jokulo2o'} <b>{$shop.name}</b> {l s='is WAITING FOR PAYMENT.' mod='jokulo2o'}
	  <br />
	  {l s='You have chosen'} <b>{$payment_channel}</b> {l s='Payment Channel Method via' mod='jokulo2o'} <b></b>{l s='DOKU' mod='jokulo2o'}</b>
		<br />
		{l s='This is your Payment Code : ' mod='jokulo2o'} <b>{$payment_code}</b> {l s='Please do the payment immediately' mod='jokulo2o'}
    <br />
    <br />
    <b>{l s='After we receive your payment, we will process your order.' mod='jokulo2o'}</b>
    <br />
    <br />
    <b>{l s='For any questions or for further information, please contact our' mod='jokulo2o'} <a href="{$urls.pages.contact}">{l s='customer support' mod='jokulo2o'}</a>.</b>
    
</p>