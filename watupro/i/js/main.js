WatuPROIntel = {};
WatuPROPractice = {};

WatuPROPractice.submit = function() {
	// please wait
	jQuery('#watuPracticeFeedback').show();
	jQuery('#watuPracticeFeedback').html("<p>" + watupro_i18n.please_wait_feedback + "</p>");	
	// hide this div
	jQuery('#questionDiv'+this.curID).hide();
	
	// submit current question and get answer
	data={"id": this.curID, "answer": this.getAnswer(this.curID), "action": "watupro_practice_submit"};
	var self=this;
	jQuery.post(WatuPRO.siteURL, data, function(msg){
		// get next id and assign to the global curID;
		var nextID=self.allIDs[0];
		var getNext=false;
		for(x in self.allIDs)
		{
			if(getNext) 
			{
				nextID=self.allIDs[x];
				break;
			}
			if(self.allIDs[x]==self.curID) getNext=true;		
		}
		
		self.curID=nextID;
		
		msg += "<p align='center'><input type='button' onclick=\"jQuery('#questionDiv"+self.curID+"').show();jQuery('#watuPracticeFeedback').hide();jQuery('#watuPROCheckButton').show();\" value='&gt;&gt;&gt;'></p>";		
		jQuery('#watuPracticeFeedback').html(msg);
		jQuery('#watuPROCheckButton').hide();
		
		// reset form
		// console.log(jQuery('#watuPROPracticeForm'+self.examID));
		jQuery('#watuPROPracticeForm'+self.examID)[0].reset();
		
		 // parse mathjax
   	if (typeof MathJax != 'undefined') MathJax.Hub.Queue(["Typeset",MathJax.Hub,"watuPracticeFeedback"]);
		
	});
}

WatuPROPractice.getAnswer = function(questionID) {
	var ansvalues=[];
	var ansgroup = '.answerof-'+questionID;   
	var answerType = jQuery('#answerType'+questionID).val();
	
	i=0;
   if(answerType == 'textarea') {
        // open end question            
      ansvalues[0]=jQuery('#textarea_q_'+questionID).val();
   }
   else {
      jQuery(ansgroup).each(function(){
		if( jQuery(this).is(':checked') || answerType=='gaps' || answerType == 'sort' || answerType == 'matrix' || answerType == 'nmatrix' || answerType == 'slider') {
				ansvalues[i] = this.value;
				i++;
 			}
 		});    
   }
   
   return ansvalues;   
}

WatuPRODep = {};

WatuPRODep.forceNumber = function(item)
{
	if(isNaN(item.value) || item.value=="") item.value = 0;
}

WatuPRODep.lockDetails = function(examID, adminURL)
{
	adminURL = adminURL || "";
	tb_show("Taking Details", adminURL + "admin-ajax.php?action=watupro_lock_details&exam_id="+examID, "admin-ajax.php");
}

// sorting answers
WatuPROSort = {};

WatuPROSort.sortable = function(event, ui) {	
	var id = jQuery(ui.item).parent().attr('id');
	var valID = id.replace('watuPROSortable', 'watuPROSortableValue');
	var positions = jQuery( "#" + id ).sortable('toArray');
	var sortedAnswers = "";
	for(i=0; i<positions.length;i++) {
		// extract only the encoded answer
		var parts = jQuery('#'+positions[i]).html().split("<!--|||");
		val = parts[1].substring(0, parts[1].length - 3);
		// val = unescape(val);																				
		sortedAnswers += val+"|";
	}
	// console.log(sortedAnswers);
	jQuery('#' + valID).val(sortedAnswers);
}

WatuPROPay = {};

WatuPROPay.payWithPoints = function(id, url, isBundle, redirectURL) {
	isBundle = isBundle || 0; 
	redirectURL = redirectURL || '';
	
	data = {"id" : id, "is_bundle" : isBundle};
	jQuery.post(url, data, function(msg){
		if(msg == 'SUCCESS') {			
			if(redirectURL) window.location = redirectURL;
			else {
				window.location = window.location + "?paid=1";
				window.location.reload(); // because of FireFox
			}
		}
		else alert(msg);
	});
}

WatuPROPay.payWithMoolaMojo = function(id, url, isBundle, redirectURL) {
	isBundle = isBundle || 0; 
	redirectURL = redirectURL || '';
	
	data = {"id" : id, "is_bundle" : isBundle};
	jQuery.post(url, data, function(msg){
		if(msg == 'SUCCESS') {			
			if(redirectURL) window.location = redirectURL;
			else {
				window.location = window.location + "?paid=1";
				window.location.reload(); // because of FireFox
			}
		}
		else alert(msg);
	});
}

// matrix answers
WatuPROIDroppable = {};
WatuPROIDroppable.drop = function(event, ui) {
	dropID = event.target.id;
	// hilite
	jQuery('#'+dropID).addClass('watupro-droppable-hover');
	
	// set answer
	var ansVal = ui.draggable[0].innerHTML;
	ansVal = ansVal.split('<!--WTPMD5');
	ansVal = ansVal[1].replace('-->', '');
	jQuery('#field-' + dropID).val(ansVal); // add value
}

// nmatrix answers
WatuPROINMatrix = {};
WatuPROINMatrix.sel = function(elt, dropID, ansNum) {
	var questionTable = jQuery('#' + dropID).closest('table');	
	
	// we need to avoid a nasty Firefox issue when browser is refreshed without shift.
	// FF keeps the field value selected but of course won't keep the CSS class to the element in the cell so the cell stays empty
	// let's go through all cells each time and fix this	
	jQuery(questionTable).find('.watupro-nmatrix-answer-field').each(function(cnt, fld) {			
		if(fld.value != '') {
			
			// make sure to give selected class to the corresponding element
			var parentTD = jQuery(fld).parent();	
			jQuery(parentTD).find('.watupro-matrix-draggable').each(function(dct, div){
				var ansVal = div.innerHTML;
				ansVal = ansVal.split('<!--WTPMD5');
				ansValParts = ansVal[1].split('-->');
				ansVal = ansValParts[0];
				if(ansVal == fld.value) jQuery(div).addClass('watupro-nmatrix-selected');
			});
		}
	});
	
	// handle un-selecting
	if(jQuery(elt).hasClass('watupro-nmatrix-selected')) {
		// remove class watupro-nmatrix-selected
		jQuery(elt).removeClass('watupro-nmatrix-selected');	
		
		// show back all elements from the cell		
		jQuery('#' + dropID + ' div.question-choices div.watupro-matrix-right div.watupro-matrix-draggable:not(.watupro-nmatrix-selected)').show('slow');
	
		// show back this element in any cells that don't have a selection yet		
		jQuery(questionTable).find('.watupro-nmatrix-answer-field').each(function(cnt, fld) {
			if(fld.value == '') {
				var parentTD = jQuery(fld).parent();				
				jQuery(parentTD).find('.watupro-nmatrix-answer-' + ansNum).show();
			}
		});
	
		// remove the hidden field value
		jQuery('#field-' + dropID).val('');
		return false;
	}

	// set answer
	var ansVal = elt.innerHTML;
	ansVal = ansVal.split('<!--WTPMD5');
	ansValParts = ansVal[1].split('-->');
	ansVal = ansValParts[0];
	jQuery('#field-' + dropID).val(ansVal); // add value
	
	// set current element class as selected
	jQuery(elt).addClass('watupro-nmatrix-selected');
	
	// hide the other options in this table cell
	jQuery('#' + dropID + ' div.question-choices div.watupro-matrix-right div.watupro-matrix-draggable:not(.watupro-nmatrix-selected)').hide('slow');
	
	// if the same element was selected in another cell we need to do call WatuPROINMatrix.unSel on it
	/*jQuery(questionTable).find('.watupro-matrix-draggable').each(function(cnt, div) {		
		if(jQuery(div).hasClass('watupro-nmatrix-answer-' + ansNum) && jQuery(div).hasClass('watupro-nmatrix-selected') && div.id != elt.id) {
			// get the proper dropID
			var divDrop = jQuery('#' + div.id).closest('td');			
			var divDropID = jQuery(divDrop).attr('id');	
			WatuPROINMatrix.sel(div, divDropID, ansNum);
		}
	});*/
	
	// hide this div from all other cells in the table
	var table = jQuery('#' + dropID).closest('table');
	var tableID = jQuery(table).attr('id');
	jQuery('#' + tableID + ' .watupro-nmatrix-answer-' + ansNum + ':not(.watupro-nmatrix-selected)').hide('slow');
}

WatuPROIntel.runTimeLogic = function(msg) {
	if(msg == 'continue' || msg == '') return true;
	
	if(msg == 'end') {
		WatuPRO.forceSubmit = true;
		WatuPRO.premature_end = 1;
		WatuPRO.submitResult();
	}
	
	if(msg == 'stop') {
		alert(watupro_i18n.cant_continue_low_correct_percent);		
		WatuPRO.nextQuestion(null, 'previous');
	}
	
	return true; // just in case msg is something else
}

WatuPROIntel.init = function(quizID) {
	
}