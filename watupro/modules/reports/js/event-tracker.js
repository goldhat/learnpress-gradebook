WTPTracker = {};

WTPTracker.track = function(category, action, label, val){
	/*console.log(category);
	console.log(action);
	console.log(label);
	console.log(val);	*/
  if (typeof ga == 'function'){  		
  	  ga('send','event', category, action, label, val);	    
  } 
  else {  	
  	if (typeof _gaq!=='undefined'){  		
     _gaq.push(['_trackEvent', category, action, label, val]);
  	}
 }
}