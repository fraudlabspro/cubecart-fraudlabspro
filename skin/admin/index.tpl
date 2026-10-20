<form action="{$VAL_SELF}" method="post" enctype="multipart/form-data">
	<div id="sage" class="tab_content">
  		<h3>FraudLabs Pro</h3>
  		<fieldset>
			<legend>{$LANG.module.config_settings}</legend>
			<div>
				<label for="status">{$LANG.common.status}</label>
				<span>
					<input type="hidden" name="module[status]" id="status" class="toggle" value="{$MODULE.status}" />
				</span>
			</div>
			<div>
				<label for="FraudLabspro">FraudLabs Pro API Key</label>
				<span>
					<input name="module[api_key]" type="text" class="textbox" value="{$MODULE.api_key}" />

					<div style="font-size:0.9em; color:#333;">
						You can sign up for a free license key at <a href="https://www.fraudlabspro.com/sign-up?r=cubecart" target="_blank">https://www.fraudlabspro.com/sign-up</a>
					</div>
				</span>
			</div>
			<div>
				<label for="sync_status">Auto Sync Order Status</label>
				<span>
					<input type="hidden" name="module[sync_status]" id="sync_status" class="toggle" value="{$MODULE.sync_status}" />
					<div style="font-size:0.9em; color:#333;">
						Send order status back to FraudLabs Pro for Completed/Cancled orders.
					</div>
				</span>
			</div>
		</fieldset>

		<div class="form_control"><input type="submit" name="save" value="{$LANG.common.save}" /></div>
	</div>

	<input type="hidden" name="token" value="{$SESSION_TOKEN}" />
</form>