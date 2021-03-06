var keyStr = "ABCDEFGHIJKLMNOP" + "QRSTUVWXYZabcdef" + "ghijklmnopqrstuv"
		+ "wxyz0123456789+/" + "=";

function getAddressString(address) {
	var addressString = [ address.attention, address.addressee, address.addr1,
			address.addr2, address.addr3,
			address.city + ' ' + address.state + ' ' + address.zip ];
	addressString = addressString.filter(function(n) {
		return n
	});
	return (addressString.join('\n'))
}

function decodeHtmlEntity(str) {
	return str.replace(/&#(\d+);/g, function(match, dec) {
	return String.fromCharCode(dec);
	});
	}

function getAddressObj(addressText, defaultBill, defaultShip) {

	// Reverse Engineering for Address Elements from AddressText
	var addrObj = {};
	var addArray = addressText.split("\n");

	// Split last element to get zip, state, city
	var cityStateZip = addArray.pop().split(' ');

	addrObj.zip = cityStateZip.pop();
	addrObj.state = cityStateZip.pop();
	addrObj.city = cityStateZip.join(' ');

	// Check for Addr3
	if (addArray.length == 5) {
		addrObj.addr3 = addArray.pop();
	}
	// Check for Addr2
	if (addArray.length == 4) {
		addrObj.addr2 = addArray.pop();
	}

	// Finish Populating Address Object
	addrObj.addr1 = addArray.pop();
	addrObj.addressee = addArray.pop();
	addrObj.attention = addArray.join(' ');
	addrObj.addresstxt = addressText;

	if (defaultBill === true) {
		addrObj.defaultbilling = 'T';
	}
	if (defaultShip === true) {
		addrObj.defaultshipping = 'T';
	}

	return (addrObj);

}

function encode64(input) {
	input = escape(input);
	var output = "";
	var chr1, chr2, chr3 = "";
	var enc1, enc2, enc3, enc4 = "";
	var i = 0;

	do {
		chr1 = input.charCodeAt(i++);
		chr2 = input.charCodeAt(i++);
		chr3 = input.charCodeAt(i++);

		enc1 = chr1 >> 2;
		enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
		enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
		enc4 = chr3 & 63;

		if (isNaN(chr2)) {
			enc3 = enc4 = 64;
		} else if (isNaN(chr3)) {
			enc4 = 64;
		}

		output = output + keyStr.charAt(enc1) + keyStr.charAt(enc2)
				+ keyStr.charAt(enc3) + keyStr.charAt(enc4);
		chr1 = chr2 = chr3 = "";
		enc1 = enc2 = enc3 = enc4 = "";
	} while (i < input.length);

	return output;
}

function decode64(input) {
	var output = "";
	var chr1, chr2, chr3 = "";
	var enc1, enc2, enc3, enc4 = "";
	var i = 0;

	// remove all characters that are not A-Z, a-z, 0-9, +, /, or =
	var base64test = /[^A-Za-z0-9\+\/\=]/g;
	if (base64test.exec(input)) {
		var error = "There were invalid base64 characters in the input text.\n"
				+ "Valid base64 characters are A-Z, a-z, 0-9, '+', '/',and '='\n"
				+ "Expect errors in decoding.";
		handleException(error)
	}
	input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

	do {
		enc1 = keyStr.indexOf(input.charAt(i++));
		enc2 = keyStr.indexOf(input.charAt(i++));
		enc3 = keyStr.indexOf(input.charAt(i++));
		enc4 = keyStr.indexOf(input.charAt(i++));

		chr1 = (enc1 << 2) | (enc2 >> 4);
		chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
		chr3 = ((enc3 & 3) << 6) | enc4;

		output = output + String.fromCharCode(chr1);

		if (enc3 != 64) {
			output = output + String.fromCharCode(chr2);
		}
		if (enc4 != 64) {
			output = output + String.fromCharCode(chr3);
		}

		chr1 = chr2 = chr3 = "";
		enc1 = enc2 = enc3 = enc4 = "";

	} while (i < input.length);

	return unescape(output);
}

function getCurrentCustomer(customer) {

	var filters = [];
	var columns = [];
	var results = [];

	columns.push(new nlobjSearchColumn("custentity_customer_source_id"));
	filters.push(new nlobjSearchFilter("custentity_customer_source_id", null,
			"is", customer.custentity_customer_source_id));
	filters.push(new nlobjSearchFilter("email", null, "is", customer.email));
	if (customer.isperson == 'F') {
		filters.push(new nlobjSearchFilter("companyname", null, "is",
				customer.companyname));
	} else {
		filters.push(new nlobjSearchFilter("firstname", null, "is",
				customer.firstname));
		filters.push(new nlobjSearchFilter("lastname", null, "is",
				customer.lastname));
	}
	filters.push(new nlobjSearchFilter("isinactive", null, "is", "F"));

	var savedsearch = nlapiCreateSearch('customer', filters, columns);
	var resultset = savedsearch.runSearch();

	var resultslice = resultset.getResults(0, 999);
	for ( var rs in resultslice) {
		results.push(resultslice[rs]['id']);
	}

	return (results);
}

function getExistingContact(customer) {

	var filters = [];
	var columns = [];
	var results = [];

	filters.push(new nlobjSearchFilter("email", null, "is", customer.email));
	filters.push(new nlobjSearchFilter("firstname", null, "is",
			customer.firstname));
	filters.push(new nlobjSearchFilter("lastname", null, "is",
			customer.lastname));

	filters.push(new nlobjSearchFilter("isinactive", null, "is", "F"));

	var savedsearch = nlapiCreateSearch('contact', filters, columns);
	var resultset = savedsearch.runSearch();

	var resultslice = resultset.getResults(0, 999);
	for ( var rs in resultslice) {
		results.push(resultslice[rs]['id']);
	}

	return (results);
}

function handleException(error) {

	var message;
	if (error instanceof nlobjError) {
		message = {
			"code" : error.getCode(),
			"id" : error.getId(),
			"details" : error.getDetails(),
			"internalId" : error.getInternalId(),
			"userEvent" : error.getUserEvent(),
			"stackTrace" : error.getStackTrace()
		};
	} else {
		message = {
			"code" : 'unexpected error',
			"details" : ((typeof error == 'object') ? error.toString() : error)
		};
	}
	nlapiLogExecution('ERROR', message.code, message.details);
	return message;

}