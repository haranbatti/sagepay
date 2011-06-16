	
// run when checkbox is clicked to synchronise the delivery details with billing details
function IsDeliverySame_clicked() {

    if (document.customerform.IsDeliverySame.checked) {

        document.customerform.DeliveryFirstnames.value = "";
        document.customerform.DeliveryFirstnames.className = "inputBoxDisable";
        document.customerform.DeliveryFirstnames.disabled = true;
        
        document.customerform.DeliverySurname.value = "";
        document.customerform.DeliverySurname.className = "inputBoxDisable";
        document.customerform.DeliverySurname.disabled = true;
        
        document.customerform.DeliveryAddress1.value = "";
        document.customerform.DeliveryAddress1.className = "inputBoxDisable";
        document.customerform.DeliveryAddress1.disabled = true;

        document.customerform.DeliveryAddress2.value = "";
        document.customerform.DeliveryAddress2.className = "inputBoxDisable";
        document.customerform.DeliveryAddress2.disabled = true; 

        document.customerform.DeliveryCity.value = "";
        document.customerform.DeliveryCity.className = "inputBoxDisable";
        document.customerform.DeliveryCity.disabled = true;

        document.customerform.DeliveryPostCode.value = "";
        document.customerform.DeliveryPostCode.className = "inputBoxDisable";
        document.customerform.DeliveryPostCode.disabled = true;

        document.customerform.DeliveryCountry.value = "";
        document.customerform.DeliveryCountry.className = "inputBoxDisable";
        document.customerform.DeliveryCountry.disabled = true;

        document.customerform.DeliveryState.value = "";
        document.customerform.DeliveryState.className = "inputBoxDisable";
        document.customerform.DeliveryState.disabled = true;

        document.customerform.DeliveryPhone.value = "";
        document.customerform.DeliveryPhone.className = "inputBoxDisable";
        document.customerform.DeliveryPhone.disabled = true;
    } 
    else 
    {
        document.customerform.DeliveryFirstnames.disabled = false;
        document.customerform.DeliveryFirstnames.className = "inputBoxEnable";
        document.customerform.DeliveryFirstnames.focus();
        document.customerform.DeliverySurname.disabled = false;
        document.customerform.DeliverySurname.className = "inputBoxEnable";
        document.customerform.DeliveryAddress1.disabled = false;
        document.customerform.DeliveryAddress1.className = "inputBoxEnable";
        document.customerform.DeliveryAddress2.disabled = false;
        document.customerform.DeliveryAddress2.className = "inputBoxEnable";
        document.customerform.DeliveryCity.disabled = false;
        document.customerform.DeliveryCity.className = "inputBoxEnable";
        document.customerform.DeliveryPostCode.disabled = false;
        document.customerform.DeliveryPostCode.className = "inputBoxEnable";
        document.customerform.DeliveryCountry.disabled = false;
        document.customerform.DeliveryCountry.className = "inputBoxEnable";
        document.customerform.DeliveryState.disabled = false;
        document.customerform.DeliveryState.className = "inputBoxEnable";
        document.customerform.DeliveryPhone.disabled = false;
        document.customerform.DeliveryPhone.className = "inputBoxEnable";
    }
}

