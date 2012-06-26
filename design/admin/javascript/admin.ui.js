$(document).ready(function()
{

	// EVENT HANDLER BINDING FOR ONIX CLASS ATTRIBUTES
	$('select[id$=mediafiletypecode], select[id$=texttypecode], select[id$=websiterole]').bind( 'change', updateTitle );
	
	// EVENT HANDLER FUNCTIONS FOR ONIX CLASS ATTRIBUTES
	function updateTitle()
	{
		$('input[id$=_title]').val( $('option:selected', this).text() ); // set title to select list text of '<mediafile|text>typecode'
	}

});