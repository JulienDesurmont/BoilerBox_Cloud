// <!-- <![CDATA[

// Project: Dynamic Date Selector (DtTvB) - 2006-03-16
// Script featured on JavaScript Kit- http://www.javascriptkit.com
// Code begin...
// Set the initial date.
var ds_i_date 	= new Date();
var ds_c_month 	= ds_i_date.getMonth() + 1;
var ds_c_year	= ds_i_date.getFullYear();
var $chargementCalendar = true;

// Get Element By Id
function ds_getel(id) {
	return document.getElementById(id);
}

// Output Element
var ds_oe 	= ds_getel('ds_calclass');
var ds_oe2 	= ds_getel('ds_calclass2');

// Container
var ds_ce 	= ds_getel('ds_conclass');
var ds_ce2 	= ds_getel('ds_conclass2');

// Output Buffering
var ds_ob 	= ''; 
function ds_ob_clean() {
	ds_ob = '';
}
function ds_ob_flush(num) {
	if(num == 1)
	{
	    ds_oe.innerHTML = ds_ob;
	}
	if(num == 2)
	{
		ds_oe2.innerHTML = ds_ob;
	}
	ds_ob_clean();
}
function ds_echo(t) {
	ds_ob += t;
}

var ds_element; // Text Element...

var ds_monthnames = [
'JAN', 'FEV', 'MAR', 'AVR', 'MAI', 'JUN',
'JUL', 'AOU', 'SEP', 'OCT', 'NOV', 'DEC'
]; // You can translate it for your language.

var ds_monthexport = [
'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'
]; // You can translate it for your language.

var ds_daynames = [
'L', 'M', 'M', 'J', 'V', 'S', 'D'
]; // You can translate it for your language.

// Calendar template
function ds_template_main_above(t,num) {
	return '<table cellpadding="2" cellspacing="0" class="ds_tbl">'
	     + '<tr>'
		 + '<td class="ds_head" style="cursor: pointer" onclick="ds_py('+num+');">&lt;&lt;</td>'
		 + '<td class="ds_head" style="cursor: pointer" onclick="ds_pm('+num+');">&lt;</td>'
		 + '<td class="ds_head" colspan="3">' + t + '</td>'
		 + '<td class="ds_head" style="cursor: pointer" onclick="ds_nm('+num+');">&gt;</td>'
		 + '<td class="ds_head" style="cursor: pointer" onclick="ds_ny('+num+');">&gt;&gt;</td>'
		 + '</tr>'
		 + '<tr>';
}

function ds_template_day_row(t) {
	return '<td class="ds_subhead">' + t + '</td>';
	// Define width in CSS, XHTML 1.0 Strict doesn't have width property for it.
}

function ds_template_new_week() {
	return '</tr><tr><td colspan="7" height="3px"></td></tr><tr>';
}
function ds_template_blank_cell(jour){
	if(jour == 0)
	{
		colspan = 6;
	}else{
		colspan = jour - 1;
	}
	return '<td colspan="' + colspan + '"></td>'
}

function ds_template_day(d, m, y, num) {
	if(num == 1)
	{
	    return '<td class="ds_cell" onclick="ds_onclick(' + d + ',' + m + ',' + y + ', 1)">' + d + '</td>';
	}
	if(num == 2)
	{
	    return '<td class="ds_cell" onclick="ds_onclick(' + d + ',' + m + ',' + y + ', 2)">' + d + '</td>';
	}
	// Define width the day row.
}

function ds_template_main_below() {
	return '</tr>'
		 + '<tr><td height="2px"></td></tr>'
	     + '</table>';
}

// This one draws calendar...
function ds_draw_calendar(m, y, num) {
	// First clean the output buffer.
	ds_ob_clean();
	// Here we go, do the header
	ds_echo (ds_template_main_above(ds_monthnames[m - 1] + ' ' + y,num));
	for (i = 0; i < 7; i ++) {
		ds_echo (ds_template_day_row(ds_daynames[i]));
	}
	// Make a date object.
	var ds_dc_date = new Date();
	ds_dc_date.setMonth(m - 1);
	ds_dc_date.setFullYear(y);
	ds_dc_date.setDate(1);
	if (m == 1 || m == 3 || m == 5 || m == 7 || m == 8 || m == 10 || m == 12) {
		days = 31;
	} else if (m == 4 || m == 6 || m == 9 || m == 11) {
		days = 30;
	} else {
		days = (y % 4 == 0) ? 29 : 28;
	}
	var first_day = ds_dc_date.getDay();
	var first_loop = 1;
	// Start the first week
	ds_echo (ds_template_new_week());
	// If sunday is not the first day of the month, make a blank cell...
	if (first_day != 1) {
		ds_echo (ds_template_blank_cell(first_day));
	}
	var j = first_day;
	for (i = 0; i < days; i ++) {
		// Today is sunday, make a new week.
		// If this sunday is the first day of the month,
		// we've made a new row for you already.
		if (j == 1 && !first_loop) {
			// New week!!
			ds_echo (ds_template_new_week());
		}
		// Make a row of that day!
		ds_echo (ds_template_day(i + 1, m, y, num));
		// This is not first loop anymore...
		first_loop = 0;
		// What is the next day?
		j ++;
		j %= 7;
	}
	// Do the footer
	ds_echo (ds_template_main_below());
	// And let's display..
	ds_ob_flush(num);
}

// A function to show the calendar.
// When user click on the date, it will set the content of t.
function ds_sh(t,num) {
	// Set the element to set...
	ds_element = t;

	//	Récupération Ajax des dates de début(num=1) ou de fin(num=2) précédemment choisies
	xhr = getXHR();
	callPathAjax(xhr,'ipc_get_date',null,false);
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	var typeDate;
	if(num == 1)
	{
	    typeDate = 'debut';
	}else{
	    typeDate = 'fin';
	}
	var datas = "typeDate="+typeDate;
	xhr.send(datas);
	//	Si aucune date précédemment sélectionnée : Création de nouvelle date à la date du jour
	/*var ds_c_month;
	var ds_c_year;
	*/
	if(xhr.responseText == '')
	{
	    // Make a new date, and set the current month and year.
            var ds_sh_date 	= new Date();
            ds_c_month 		= ds_sh_date.getMonth() + 1;
            ds_c_year 		= ds_sh_date.getFullYear();
	}else{
	    //	Si une période a déjà été choisie : Sélection de la même période
	    var tabDate 	= JSON.parse(xhr.responseText);
	    ds_c_month 		= tabDate['mm'];
	    ds_c_year  		= tabDate['aaaa'];
	}

	// Draw the calendar
	ds_draw_calendar(ds_c_month, ds_c_year, num);
	
	if(num == 1)
	{ // To change the position properly, we must show it first.
	   $('#ds_conclass').removeClass('cacher');
	   //ds_ce.className='ds_box';
	}
	if(num == 2)
	{ // To change the position properly, we must show it first.
	  $('#ds_conclass2').removeClass('cacher');
	  //ds_ce2.className='ds_box';
	}
}


// Hide the calendar.
function ds_hi(num) {
	if(num == 1)
	{ 
		//ds_ce.className='ds_box cacher';
		$('#ds_conclass').addClass('cacher');
	}
	if(num == 2)
	{
		$('#ds_conclass2').addClass('cacher');
		//ds_ce2.className='ds_box cacher';
	}
}

// Moves to the next month...
function ds_nm(num) {
	// Increase the current month.
	ds_c_month ++;
	// We have passed December, let's go to the next year.
	// Increase the current year, and set the current month to January.
	if (ds_c_month > 12) {
		ds_c_month = 1; 
		ds_c_year++;
	}
	// Redraw the calendar.
	ds_draw_calendar(ds_c_month, ds_c_year, num);
}

// Moves to the previous month...
function ds_pm(num) {
	ds_c_month = ds_c_month - 1; // Can't use dash-dash here, it will make the page invalid.
	// We have passed January, let's go back to the previous year.
	// Decrease the current year, and set the current month to December.
	if (ds_c_month < 1) {
		ds_c_month = 12; 
		ds_c_year = ds_c_year - 1; // Can't use dash-dash here, it will make the page invalid.
	}
	// Redraw the calendar.
	ds_draw_calendar(ds_c_month, ds_c_year, num);
}

// Moves to the next year...
function ds_ny(num) {
	// Increase the current year.
	ds_c_year++;
	// Redraw the calendar.
	ds_draw_calendar(ds_c_month, ds_c_year, num);
}

// Moves to the previous year...
function ds_py(num) {
	// Decrease the current year.
	ds_c_year = ds_c_year - 1; // Can't use dash-dash here, it will make the page invalid.
	// Redraw the calendar.
	ds_draw_calendar(ds_c_month, ds_c_year, num);
}

// Format the date to output.
function ds_format_date(d, m, y) {
	// 2 digits month.
	m2 = '00' + m;
	m2 = m2.substr(m2.length - 2);
	// 2 digits day.
	d2 = '00' + d;
	d2 = d2.substr(d2.length - 2);
	// YYYY-MM-DD
//	return y + '-' + m2 + '-' + d2;
//	return d2 + ' ' + m2 + ' ' + y;
	return d2 + ' ' + ds_monthexport[m - 1] + ' ' + y;
}

// When the user clicks the day.
function ds_onclick(d, m, y, num) {
	// Hide the calendar.
	ds_hi(num);
	// Set the value of it, if we can.
	if (typeof(ds_element.value) != 'undefined') {
		ds_element.value = ds_format_date(d, m, y);
	// Maybe we want to set the HTML in it.
	} else if (typeof(ds_element.innerHTML) != 'undefined') {
		ds_element.innerHTML = ds_format_date(d, m, y);
	// I don't know how should we display it, just alert it to user.
	} else {
		alert (ds_format_date(d, m, y));
	}
}

// ]]> -->
