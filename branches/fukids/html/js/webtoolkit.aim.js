/**
*
*  AJAX IFRAME METHOD (AIM)
*  http://www.webtoolkit.info/
*
**/

function hash(string, length) {
 var length = length ? length : 32;
 var start = 0;
 var i = 0;
 var result = '';
 filllen = length - string.length % length;
 for(i = 0; i < filllen; i++){
  string += "0";
 }
 while(start < string.length) {
  result = stringxor(result, string.substr(start, length));
  start += length;
 }
 return result;
}

//note 将两个字符串进行异或运算，结果为英文字符组合
function stringxor(s1, s2) {
 var s = '';
 var hash = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
 var max = Math.max(s1.length, s2.length);
 for(var i=0; i<max; i++) {
  var k = s1.charCodeAt(i) ^ s2.charCodeAt(i);
  s += hash.charAt(k % 52);
 }
 return s;
}

var evalscripts = new Array();
function evalscript(s) {
 if(s.indexOf('<script') == -1) return s;
 var p = /<script[^\>]*?src=\"([^\>]*?)\"[^\>]*?(reload=\"1\")?(?:charset=\"([\w\-]+?)\")?><\/script>/ig;
 var arr = new Array();
 while(arr = p.exec(s)) {
  appendscript(arr[1], '', arr[2], arr[3]);
 }
 s = s.replace(p, '');
 p = /<script(.*?)>([^\x00]+?)<\/script>/ig;
 while(arr = p.exec(s)) {
  appendscript('', arr[2], arr[1].indexOf('reload=') != -1);
 }
 return s;
}

function appendscript(src, text, reload, charset) {
 var id = hash(src + text);
 if(!reload && in_array(id, evalscripts)) return;
 if(reload && $(id)) {
  $(id).parentNode.removeChild($(id));
 }

 evalscripts.push(id);
 var scriptNode = document.createElement("script");
 scriptNode.type = "text/javascript";
 scriptNode.id = id;
 scriptNode.charset = charset;
 try {
  if(src) {
   scriptNode.src = src;
  } else if(text){
   scriptNode.text = text;
  }
  $('append_parent').appendChild(scriptNode);
 } catch(e) {}
}

AIM = {

	frame : function(c) {

		var n = 'f' + Math.floor(Math.random() * 99999);
		var d = document.createElement('DIV');
		d.innerHTML = '<iframe style="display:none" src="about:blank" id="'+n+'" name="'+n+'" onload="AIM.loaded(\''+n+'\')"></iframe>';
		document.body.appendChild(d);

		var i = document.getElementById(n);
		if (c && typeof(c.onComplete) == 'function') {
			i.onComplete = c.onComplete;
		}

		return n;
	},

	form : function(f, name) {
		f.setAttribute('target', name);
	},

	submit : function(f, c) {
		AIM.form(f, AIM.frame(c));
		if (c && typeof(c.onStart) == 'function') {
			return c.onStart();
		} else {
			return true;
		}
	},

	loaded : function(id) {
		var i = document.getElementById(id);
		if (i.contentDocument) {
			var d = i.contentDocument;
		} else if (i.contentWindow) {
			var d = i.contentWindow.document;
		} else {
			var d = window.frames[id].document;
		}
		if (d.location.href == "about:blank") {
			return;
		}

		if (typeof(i.onComplete) == 'function') {
			i.onComplete(d.body.innerHTML);
		}
	}

}
