	Function.prototype.method = function(name, func) {
		if (!this.prototype[name]) {
			this.prototype[name] = func;
		}		
	};
	
	var $http = (function() {

		var doAjax = function(callback, userdata, useroptions, type) {
	
			var q = new $.Deferred();
	
			var data = $.extend({
				action: ajax.action,
				callback: callback
			}, userdata || {});
			
			var options = $.extend({
				url: ajax.url,
				data: data,
				type: type,
				dataType: "json"
			}, useroptions || {});
	
			console.log("Options", options);			

			$.ajax(options)
			.done(function(data, msg, xhr) {
				q.resolve(data);	
				console.log("Success", arguments);
			})
			.error(function(xhr, msg, resp) {
				q.reject(xhr, msg, resp);
				console.log("Error", arguments);
			});
			
			return q.promise();
		};	

		return {
			post: function(callback, userdata, useroptions) {
				return doAjax(callback, userdata, useroptions, "POST");
			},
			
			get: function(callback, userdata, useroptions) {
				return doAjax(callback, userdata, useroptions, "GET");
			},
			
			getTemplate: function(callback, userdata, useroptions) {
				useroptions = useroptions || {};
				useroptions.dataType = "html";
				
				return doAjax(callback, userdata, useroptions, "POST");			
			}
			
		}
	})();
