function trim(str) {
	return str.replace(/(^\s*)|(\s*$)/g, "");
}

//格式化价格
function formatPrice(price) {
	price = parseFloat(price) //转换为浮点数
	if(isNaN(price)) {
		return false;
	}
	return price.toFixed(2);
}
/**
 * 格式化时间的辅助类，将一个时间转换成x小时前、y天前等
 */
var dateUtils = {
	UNITS: {
		'年': 31557600000,
		'月': 2629800000,
		'天': 86400000,
		'小时': 3600000,
		'分钟': 60000,
		'秒': 1000
	},

	humanize: function(milliseconds) {
		var humanize = '';
		mui.each(this.UNITS, function(unit, value) {
			if(milliseconds >= value) {
				humanize = Math.floor(milliseconds / value) + unit + '前';
				return false;
			}
			return true;
		});
		return humanize || '刚刚';
	},
	format: function(dateStr) {
		var date = new Date(parseInt(dateStr) * 1000);
		var diff = Date.now() - date.getTime();
		if(diff < this.UNITS['天']) {
			return this.humanize(diff);
		}
		return date.toLocaleString().replace(/:\d{1,2}$/, ' ');
	}
};

/*
 * 更新用户位置的方法，注意：自己只能更新自己的，用session判断更新谁的
 * 传入map：Point对象
 * callback回掉函数
 * ecallback错误回掉函数
 */
var updatePos = function(pos, callback, ecallback) {
	var longitude = pos.longitude;
	var latitude = pos.latitude;
	//将位置发送到服务器
	app.request('Service', 'updateUserPos', {
		'longitude': longitude,
		'latitude': latitude
	}, function(res) {
		//服务器方登陆失效
		if(res.login == 0) {
			mui.toast(res.info);
			return app.toLogin(res.info);
		}
		if(callback && typeof callback == "function")
			return callback();
	}, function() {
		if(ecallback && typeof ecallback == "function")
			return ecallback();
	}, "none");
}

//方法一扩展（C#中PadLeft、PadRight）
String.prototype.PadLeft = function(len, charStr) {
	var s = this + '';
	return new Array(len - s.length + 1).join(charStr, '') + s;
}
String.prototype.PadRight = function(len, charStr) {
	var s = this + '';
	return s + new Array(len - s.length + 1).join(charStr, '');
}

//格式化订单编号
function format_id(id) {
	return id.toString().PadLeft(11, '0');
}

function request(ctl, act, dataObj, callback, ecallback) {
	ctl = ctl || "";
	act = act || "";
	callback = callback || $.noop;
	ecallback = ecallback || $.noop;
	waiting = waiting || true;

	if(ctl == "" || act == "")
		return;
	var url = HTTP_DOMAIN + ctl + "/" + act;
	console.log(url);
	console.log(JSON.stringify(dataObj));
	mui.ajax(url, {
		data: dataObj,
		dataType: 'json',
		timeout: 5000,
		type: 'post',
		beforeSend: function(XMLHttpRequest) {
		},
		complete: function(XMLHttpRequest, textStatus) {
		},
		success: function(response) {
			if(callback && typeof callback == "function")
				return callback(response);
			else
				return;
		},
		error: function(xhr, type, error) {
			//检查超时和网络
			if(type === "timeout")
				mui.toast("连接超时，请检查网络");
			//错误信息
			else {
				switch(xhr.status) {
					case 500:
						mui.toast("很抱歉，服务器错误！");
						break;
					case 503:
						mui.toast("很抱歉，服务器超时！");
						break;
					case 404:
						mui.toast("很抱歉，请求方法丢失或不存在！");
						break;
					default:
						mui.toast("未知网络错误，请稍后重试！");
						break;

				}
			}

			if(ecallback && typeof ecallback == "function")
				return ecallback(xhr); //将xhr交给具体调用者检查
			else
				return;
		}
	});
};