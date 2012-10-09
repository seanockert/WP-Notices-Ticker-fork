function addEventToObject(obj, evt, func)
{
	var oldhandler = obj[evt];
	obj[evt] = (typeof obj[evt] != 'function') ? func : function(ev){oldhandler(ev); func(ev);};
}

function NoticesTicker(Name, Count, Pause)
{
	NoticesTicker.insts[(this.oid = NoticesTicker.insts.length)] = this;

	this.iCurrentNewsItem = 0;
	this.tTM = null;
	this.iCycleCount = this.iStartCount = Count;
	this.iFadeCount = 110;
	this.Name = Name;
	this.Pause = Pause;

	this.Init = function(notices) {
		this.Notices = notices;
		SetInnerHTML(this);
	};

	this.PauseTicker = function()
	{
		if(this.tTM) { clearTimeout(this.tTM); }
		var oFadeItem = document.getElementById(this.Name);
		oFadeItem.style.KHTMLOpacity = 0.999;
		oFadeItem.style.MozOpacity = 0.999;
		oFadeItem.style.opacity = 0.999;
		this.iFadeCount = 100;
	};

	this.RestartTicker = function()
	{
		this.iCycleCount = this.iStartCount;
		CycleTicker(this);
	};
}

NoticesTicker.insts = [];

function SetInnerHTML(r)
{
	var oElement = document.getElementById(r.Name);
	oElement.innerHTML = r.Notices[r.iCurrentNewsItem];
	if(r.Pause) {
		addEventToObject(oElement, 'onmouseover', function() { r.PauseTicker() } );
		addEventToObject(oElement, 'onmouseout', function() { r.RestartTicker() } );
	}
	oElement.style.KHTMLOpacity = 0;
	oElement.style.MozOpacity = 0;
	oElement.style.opacity = 0;
	Fade('in', r);
}

function CycleTicker(r)
{
	if(r.tTM) { clearTimeout(r.tTM); }
	if(r.iCycleCount < 0) {
		r.iCycleCount = r.iStartCount;
		Fade('out', r);
	}
	else {
		r.iCycleCount -= 100;
		r.tTM = setTimeout('CycleTicker(NoticesTicker.insts[' + r.oid + ']);', 100);
	}
}

function Fade(dir, r)
{
	if(r.tTM) { clearTimeout(r.tTM); }
	if(r.iFadeCount < 0) {
		r.iFadeCount = 110;
		if(dir == 'in') { CycleTicker(r); }
		else {
			r.iCurrentNewsItem = (r.iCurrentNewsItem < r.Notices.length - 1) ? r.iCurrentNewsItem + 1 : 0;
			SetInnerHTML(r);
		}
	}
	else {
		r.iFadeCount -= 10;
		if(r.iFadeCount <= 100) {
			var oFadeItem = document.getElementById(r.Name);
			if(dir == 'in') { var opac = 1 - (((r.iFadeCount / 100) > 0.999) ? 0.999 : (r.iFadeCount / 100)); }
			else { var opac = ((r.iFadeCount / 100) > 0.999) ? 0.999 : (r.iFadeCount / 100); }
			oFadeItem.style.KHTMLOpacity = opac;
			oFadeItem.style.MozOpacity = opac;
			oFadeItem.style.opacity = opac;
		}
		r.tTM = setTimeout('Fade("' + dir + '", NoticesTicker.insts[' + r.oid + ']);', 50);
	}
}
