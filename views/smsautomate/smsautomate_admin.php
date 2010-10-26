
<h1> SMS Automate - Settings</h1>
<?php print form::open(); ?>

	<?php if ($form_error) { ?>
	<!-- red-box -->
		<div class="red-box">
			<h3><?php echo Kohana::lang('ui_main.error');?></h3>
			<ul>
				<?php
				foreach ($errors as $error_item => $error_description)
				{
				// print "<li>" . $error_description . "</li>";
				print (!$error_description) ? '' : "<li>" . $error_description . "</li>";
				}
				?>
			</ul>
			</div>
	<?php } ?>


	<?php  if ($form_saved) {?>
		<!-- green-box -->
		<div class="green-box">
		<h3><?php echo Kohana::lang('ui_main.configuration_saved');?></h3>
		</div>
	<?php } ?>

<h4> 
	<br/> For incoming SMS messages to work with this plugin the following format and ordering must be used.<br/>
	
	<div style="padding:10px;margin:20px; font-style:italic; border: 1px solid black;"> &lt;Code Word&gt;&lt;delimiter&gt;
	&lt;Decimal Degree Latitude&gt;&lt;delimiter&gt;&lt;Decimal Degree Longitude&gt;&lt;delimiter&gt;
	&lt;Title&gt;&lt;delimiter&gt;&lt;Location Description&gt;&lt;delimiter&gt;
	&lt;Event Description&gt;&lt;delimiter&gt;&lt;Category Codes seperated by commas&gt;</div><br/>
	
	So for example if we use ';' as our delimiter and "abc" as our code word then the following:<br/>
	
	<div style="padding:10px;margin:20px; font-style:italic; border: 1px solid black;">abc;7.77;-9.42;My Title;Zorzor, Liberia;The description of the event;1,3,4</div><br/><br/>
	
	This would be converted into a report at latitude 7.77 and longitude -9.42, calling this location "Zorzor Liberia", with a title of "My Title", a description of 
	"The description of the event", and tagged under catgories 1, 3 and 4. 
	<br/>
	<br/>
	To figure out a category's ID number look at the status bar when mousing over the edit or delete link in the Catgories Manage Page in the
	administrative interface. This should be located in admin/manage on your Ushahidi site.
	<br/>
	<br/>
	The Location Description, Event Description and Category fields are optional. A message must have a code word, lat, lon, and title to be parsed.
	<br/>
	<br/> Please becareful with these settings, choosing an easy to guess code word 
	will make your site an easy target for malicious groups wishing to spread mis-information. Also by choosing a delimiter 
	that may be used in the message you run the risk of having malformed SMS messages that can't be properly read.
<h4>
<br/>
<br/>


<div>
	<div class="row">
		
		<h4>What character should be the delimiter between fields in a text message?</h4>
		<h6 style="margin-top:1px; padding-top:1px;margin-bottom:1px; padding-bottom:1px;">
			Don't use a comma, "," as this is the delimiter for category IDs, and also a fairly commonly used punctionation mark. 
			<br/>Use something more obscure like a semi-colon, ";" or an ampersand. "&amp;".
		</h6>
		<?php print form::input('delimiter', $form['delimiter'], ' class="text"'); ?>		
	</div>
	<br/>
	<div class="row">
		<h4>What code word should be used to make sure that the SMS is from a trusted user?</h4>
		<h6 style="margin-top:1px; padding-top:1px;margin-bottom:1px; padding-bottom:1px;">
			This is case insensative. For example "AbC" and "abc" will be treated as the same code word.
		</h6>
		<?php print form::input('code_word', $form['code_word'], ' class="text"'); ?>
		
	</div>
	<br/>
	<div class="row">
		<h4>White listed phone numbers</h4>
		<h6 style="margin-top:1px; padding-top:1px;margin-bottom:1px; padding-bottom:1px;">
			Enter a list of phone numbers, each number on a different line, that are allowed to send in SMSs that are automatically made into reports. 
			<br/>Numbers must be in the exact same format as when they're recieved. If you want any number to be able to use this leave the list blank.
		</h6>
		<?php print form::textarea('whitelist', $form['whitelist'], ' rows="12" cols="40"') ?>		
	</div>
	
	
	
	
</div>
<br/>

<input type="image" src="<?php echo url::base() ?>media/img/admin/btn-save-settings.gif" class="save-rep-btn" style="margin-left: 0px;" />

<?php print form::close(); ?>

