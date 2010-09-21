function populate(themonth)
{
	var theform = document.getElementById('add_grade')

	var firsttime = new Date(theform.year.value, theform.month.options[themonth].value,1);
	var thedifference = firsttime - 86400000;
	var secondtime = new Date(thedifference);
	var daysinthemonth = secondtime.getDate();
	
	for (var i = 0; i < theform.day.length; i++)
	{
		theform.day.options[0] = null;
	}

	for (var i = 0; i < daysinthemonth; i++)
	{
		theform.day.options[i] = new Option(i+1);
	}

document.add_grade.day.options[0].selected = true;

}


// shows/hides 'span'
function toggle_display(span)
{
        if(span == "idspan")
        {
                if(document.getElementById('idspan').style.display == "none")
                {
                	document.getElementById('menu').style.display = "none";
                	document.getElementById('copyright').style.display = "none";
						document.getElementById('namespan').style.display = "none";
						document.getElementById('printlink').innerHTML = "normal view";
                }

					else
					{
						document.getElementById('menu').style.display = "";
						document.getElementById('copyright').style.display = "";
						document.getElementById('namespan').style.display = "";
						document.getElementById('printlink').innerHTML = "print view";
					}
        }


        if(document.getElementById(span).style.display == "none")
        {
                document.getElementById(span).style.display = "";
        }

        else
        {
                document.getElementById(span).style.display = "none";
        }
}

