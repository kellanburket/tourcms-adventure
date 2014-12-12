var Rate = function(kind, rate) {
	var self = {};
	
	self.getSingular = function(kind) {
		if (kind.match(/children/i)) {
			return kind.replace(/children/i, "child");
		} else if(kind.match(/.*?ies$/i)) {
			return kind.replace(/(.*?)(ies)$/i, "$1y");
		} else if(kind.match(/.*?[lr]ves$/i)) {
			return kind.replace(/(.*?[lr])ves$/i, "$1f");
		} else if(kind.match(/.*?[^aeiou]{2,}es$/i)) {
			return kind.replace(/(.*?[^aeiou]{2,})(es)$/i, "$1");
		} else if(kind.match(/.*?oes$/i)) {
			return kind.replace(/(.*?o)es$/i, "$1");
		} else if(kind.match(/.*?xes$/i)) {
			return kind.replace(/(.*?x)es$/i, "$1");
		} else {
			return kind.replace(/(.*?)s$/i, "$1");
		}
	}
	
	//Kind treated as private member variable. Please call getKind()
	self.kind = kind;
	self.single = self.getSingular(kind);
	self.plural = kind;
	
	self.rate = rate;
	self.number = 0;
	self.revised_rate = rate;
	self.promos = [];
	
	self.getTotal = function() {
		return self.revised_rate * self.number;
	}
	
	self.getRevisedRate = function(promos) {
		return self.revised_rate; 
	}
	
	self.calculateTax = function(rate, tax) {
		var new_rate = (parseFloat(rate) * parseFloat(tax/100)) + parseFloat(rate);
		return new_rate;
	}
	
	self.setRevisedRate = function(promos) {
		for (var i in promos) {
			var already_set = false;
			for (var ii in self.promos) {
				if (self.promos[ii].name == promos[i].name) {
					already_set = true;
					break;
				}
			}
			
			if (!already_set) {
				self.promos.push(promos[i]);			
				self.revised_rate = promos[i].getDiscountedRate(self.revised_rate);
			}
			
		}
	}
	
	self.setNumber = function(num) {
		self.number = parseInt(num);
		self.total = self.number * parseFloat(self.rate);
	}
	
	self.getKind = function() {
		if (self.number == 1) {
			return self.single;		
		} else {
			return self.plural;
		}
	}
	
	return self;
}
	