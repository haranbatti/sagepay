


// Used in all pages to submit a form and optionally set a hidden 
// form varaible called 'navigate' to direct navgiation
function submitForm(formName, navigateValue) {
	if (navigateValue != null && navigateValue != "") {
		document.forms[formName].navigate.value = navigateValue;
	}
    document.forms[formName].submit();
}
