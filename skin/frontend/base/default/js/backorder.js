var backorder = Class.create({
    afterInit: function() {
        this.orderbeforestring = false;
        if (this.isproduct) {
            if (this.productestimate || this.orderbefore) {
                if (this.producttype == "grouped") {
                    this.initGrouped();
                } else if (this.producttype == "configurable") {
                    this.initConfigurable();
                } else if (this.producttype == "bundle") {
                    this.initBundle();
                } else {
                    if (this.productestimate) {
                        $$(".availability")[0].insert({after: "<p class=\"dispatch-estimate\">" + this.estimatetext + ": <span>" + this.productestimate + "</span></p>"});
                    } else {
                        $$(".availability")[0].insert({after: "<div class=\"dispatch-nolead\">" + this.getOrderBeforeString() + "</div>"});
                    }
                }
            }
        } else if (this.iscart) {
            $$("#shopping-cart-table .product-name").each(function(e) {
                var estimate = this.cartestimates.shift();
                var itemid = this.itemids.shift();
                var checked = "";
                if (this.hasaccepted || this.acceptedids.indexOf(itemid) >= 0) {
                    checked = "checked ";
                }
                if (!estimate && this.orderbefore) {
                    e.insert({after: "<div class=\"dispatch-nolead\">" + this.getOrderBeforeString() + "</div>"});
                } else if (estimate) {
                    e.insert({after: "<div class=\"dispatch-estimate\">" + this.estimatetext + ": <span>" + estimate + "</span></div>"});
                    if (this.acceptenabled) {
                        e.next().insert({after: "<div class=\"backorder-accept\"><input " + checked + "type=\"checkbox\" class=\"checkbox\" name=\"backorder[" + itemid + "]\" /><span>" + this.delayedtext + "</span></div>"});
                    }
                }
            }.bind(this));
            this.addCheckboxListeners();
        }
    },
    getOrderBeforeString: function() {
        if (!this.orderbeforestring) {
            var cutoff = new Date(this.cutoff * 1000);
            var now = new Date(this.now * 1000);
            var difference = this.cutoff - this.now;
            var minutes = difference / 60;
            var hours = Math.floor(minutes / 60);
            minutes -= hours * 60;
            minutes = Math.floor(minutes);
            if (!hours) {
                var estimate = minutes + " minutes";
            } else {
                var estimate = hours + " hours and " + minutes + " minutes";
            }
            if (cutoff.getDay() > now.getDay()) {
                this.orderbeforestring = this.orderbeforetomorrowtext + " <span>" + estimate + "</span>";
            } else {
                this.orderbeforestring = this.orderbeforetodaytext + " <span>" + estimate + "</span>";
            }
        }
        
        return this.orderbeforestring;
    },
    initGrouped: function() {
        $$(".grouped-items-table input").each(function(e) {
            var productid = e.name.match(/[0-9]+/);
            if (this.productestimate[productid]) {
                e.up("tr").down().insert({bottom: "<p class=\"dispatch-estimate\">" + this.estimatetext + ": <span>" + this.productestimate[productid] + "</span></p>"});
            } else if (this.orderbefore) {
                e.up("tr").down().insert({bottom: "<p class=\"dispatch-nolead\">" + this.getOrderBeforeString() + "</p>"});
            }
        }.bind(this));
    },
    initConfigurable: function() {
        this.addConfigurableSelectListeners();
        this.updateConfigurableEstimate();
    },
    addConfigurableSelectListeners: function() {
        $$(".product-options select").each(function(e) {
            e.observe("change", function(el) {
                this.updateConfigurableEstimate();
            }.bind(this));
        }.bind(this));
    },
    updateConfigurableEstimate: function() {
        this.removeEstimate();
        var productid = spConfig.getIdOfSelectedProduct();
        if (productid) {
            if (this.productestimate[productid]) {
                $$(".availability")[0].insert({after: "<p class=\"dispatch-estimate\">" + this.estimatetext + ": <span>" + this.productestimate[productid] + "</span></p>"});
            } else if (this.orderbefore) {
                $$(".availability")[0].insert({after: "<p class=\"dispatch-nolead\">" + this.getOrderBeforeString() + "</p>"});
            }
        }
    },
    initBundle: function() {
        this.addBundleOptionListeners();
        this.updateBundleEstimate();
    },
    addBundleOptionListeners: function() {
        this.addBundleSelectListeners();
        this.addBundleInputListeners();
    },
    addBundleSelectListeners: function() {
        $$(".product-options select").each(function(e) {
            e.observe("change", function(el) {
                this.updateBundleEstimate();
            }.bind(this));
        }.bind(this));
    },
    addBundleInputListeners: function() {
        $$(".product-options input").each(function(e) {
            e.observe("change", function(el) {
                this.updateBundleEstimate();
            }.bind(this));
        }.bind(this));
    },
    updateBundleEstimate: function() {
        this.removeEstimate();
        var estimates = [];
        var selected = $("product_addtocart_form").serialize(true);
        $H(selected).each(function(e) {
            if (e.key.indexOf("bundle_option") == 0 && e.value != "") {
                var bundleid = e.key.match(/[0-9]+/);
                var value = e.value.toString();
                if (value.indexOf(",")) {
                    var values = value.split(",");
                } else {
                    var values = [value];
                }
                values.each(function(value) {
                    var estimate = this.getBundleEstimate(bundleid, value);
                    if (estimate) {
                        estimates.push(estimate);
                    }
                }.bind(this));
            }
        }.bind(this));
        var estimate = this.getLongestBundleEstimate(estimates);
        if (estimate) {
            $$(".availability")[0].insert({after: "<p class=\"dispatch-estimate\">" + this.estimatetext + ": <span>" + estimate + "</span></p>"});
        } else if (this.orderbefore) {
            $$(".availability")[0].insert({after: "<p class=\"dispatch-nolead\">" + this.getOrderBeforeString() + "</p>"});
        }
    },
    getBundleEstimate: function(bundleid, value) {
        if (this.productestimate[bundleid]) {
            if (this.productestimate[bundleid][value]) {
                var epoch = this.productestimate[bundleid][value]["epoch"];
                var estimate = this.productestimate[bundleid][value]["estimate"];

                return [epoch, estimate];
            }
        }
        
        return false;
    },
    getLongestBundleEstimate: function(estimates) {
        var epoch = 0;
        var estimate = "";
        estimates.each(function(e) {
            if (e[0] > epoch) {
                epoch = e[0];
                estimate = e[1];
            }
        }.bind(this));
        
        if (estimate) {
            return estimate;
        }
        
        return false;
    },
    removeEstimate: function() {
        if ($$(".dispatch-estimate")[0]) {
            $$(".dispatch-estimate")[0].remove();
        } else if ($$(".dispatch-nolead")[0]) {
            $$(".dispatch-nolead")[0].remove();
        }
    },
    addCheckboxListeners: function() {
        if (this.acceptenabled) {
            $$(".backorder-accept").each(function(e) {
                e.down("input").observe("click", function(el) {
                    this.updateAccepted();
                }.bind(this));
                e.down("span").observe("click", function(el) {
                    if (el.target.previous().checked) {
                        el.target.previous().checked = false;
                    } else {
                        el.target.previous().checked = true;
                    }
                    this.updateAccepted();
                }.bind(this));
            }.bind(this));
        }
    },
    updateAccepted: function() {
        var accepted = true;
        var itemids = [];
        $$(".backorder-accept input").each(function(e) {
            if (e.checked) {
                itemids.push(e.name.match(/[0-9]+/));
            } else {
                accepted = false;
            }
        }.bind(this));
        new Ajax.Request(this.acceptedurl, {
            parameters: {accepted: accepted, itemids: itemids.join()}
        });
    }
});