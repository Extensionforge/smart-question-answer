(function ($) {
	SmartQa.models.Filter = Backbone.Model.extend({
		defaults: {
			active: false,
			label: '',
			value: ''
		}
	});

	SmartQa.collections.Filters = Backbone.Collection.extend({
		model: SmartQa.models.Filter
	});

	SmartQa.activeListFilters = $('#asqa_current_filters').length > 0 ? JSON.parse($('#asqa_current_filters').html()) : {};
	SmartQa.views.Filter = Backbone.View.extend({
		//tagName: 'li',
		id: function () {
			return this.model.id;
		},
		nameAttr: function () {
			if (this.multiple) return '' + this.model.get('key') + '[]';
			return this.model.get('key');
		},
		isActive: function () {
			if (this.model.get('active'))
				return this.model.get('active');

			if (this.active)
				return this.active;

			var get_value = SmartQa.getUrlParam(this.model.get('key'));
			if (!_.isEmpty(get_value)) {
				var value = this.model.get('value');
				if (!_.isArray(get_value) && get_value === value)
					return true;
				if (_.contains(get_value, value)) {
					this.active = true;
					return true;
				}
			}

			this.active = false;
			return false;
		},
		className: function () {
			return this.isActive() ? 'active' : '';
		},
		inputType: function () {
			return this.multiple ? 'checkbox' : 'radio';
		},
		initialize: function (options) {
			this.model = options.model;
			this.multiple = options.multiple;
			this.listenTo(this.model, 'remove', this.removed);
		},
		template: '<label><input type="{{inputType}}" name="{{name}}" value="{{value}}"<# if(active){ #> checked="checked"<# } #>/><i class="apicon-check"></i>{{label}}<# if(typeof color !== "undefined"){ #> <span class="asqa-label-color" style="background: {{color}}"></span><# } #></label>',
		events: {
			'change input': 'clickFilter'
		},
		render: function () {
			var t = _.template(this.template);
			var json = this.model.toJSON();
			json.name = this.nameAttr();
			json.active = this.isActive();
			json.inputType = this.inputType();
			this.removeHiddenField();
			this.$el.html(t(json));
			return this;
		},
		removeHiddenField: function () {
			$('input[name="' + this.nameAttr() + '"][value="' + this.model.get('value') + '"]').remove();
		},
		clickFilter: function (e) {
			e.preventDefault();
			$(e.target).closest('form').submit();
		},
		removed: function () {
			this.remove();
		}
	});

	SmartQa.views.Filters = Backbone.View.extend({
		className: 'asqa-dropdown-menu',
		searchTemplate: '<div class="asqa-filter-search"><input type="text" search-filter placeholder="' + aplang.search + '" /></div>',
		template: '<button class="asqa-droptogg apicon-x"></button><filter-items></filter-items>',
		initialize: function (options) {
			this.model = options.model;
			this.multiple = options.multiple;
			this.filter = options.filter;
			this.nonce = options.nonce;
			this.listenTo(this.model, 'add', this.added);
			this.listenTo(this.model, 'reset', this.destroy);
		},
		events: {
			'keypress [search-filter]': 'searchInput'
		},
		renderItem: function (filter) {
			var view = new SmartQa.views.Filter({ model: filter, multiple: this.multiple });
			this.$el.find('filter-items').append(view.render().$el);
		},
		render: function () {
			var self = this;
			if (this.multiple)
				this.$el.append(this.searchTemplate);

			this.$el.append(this.template);
			this.model.each(function (filter) {
				self.renderItem(filter);
			});
			return this;
		},
		search: function (q, e) {
			var self = this;

			var args = { __nonce: this.nonce, asqa_ajax_action: 'load_filter_' + this.filter, search: q, filter: this.filter };

			SmartQa.showLoading(e);
			SmartQa.ajax({
				data: args,
				success: function (data) {
					SmartQa.hideLoading(e);
					if (data.success) {
						self.nonce = data.nonce;
						while (model = self.model.first()) {
							model.destroy();
						}
						self.model.add(data.items);
					}
				}
			});
		},
		searchInput: function (e) {
			var self = this;
			clearTimeout(this.searchTO);
			this.searchTO = setTimeout(function () {
				self.search($(e.target).val(), e.target);
			}, 600);
		},
		added: function (model) {
			this.renderItem(model);
		},
		destroy: function () {
			console.log('deleted')
			this.undelegateEvents();
			this.$el.removeData().unbind();
			this.remove();
			Backbone.View.prototype.remove.call(this);
		}
	});

	SmartQa.views.List = Backbone.View.extend({
		el: '#asqa-filters',
		initialize: function () {

		},
		events: {
			'click [asqa-filter]': 'loadFilter',
			'click #asqa-filter-reset': 'resetFilter'
		},
		loadFilter: function (e) {
			e.preventDefault();
			var self = this;

			SmartQa.showLoading(e.currentTarget);
			var q = JSON.parse($(e.currentTarget).attr('apquery'));
			q.asqa_ajax_action = 'load_filter_' + q.filter;

			SmartQa.ajax({
				data: q,
				success: function (data) {
					SmartQa.hideLoading(e.currentTarget);
					$(e.currentTarget).addClass('loaded');
					var filters = new SmartQa.collections.Filters(data.items);
					var view = new SmartQa.views.Filters({ model: filters, multiple: data.multiple, filter: q.filter, nonce: data.nonce });
					$(e.currentTarget).parent().find('.asqa-dropdown-menu').remove()
					$(e.currentTarget).after(view.render().$el);
				}
			});
		},
		resetFilter: function (e) {
			$('#asqa-filters input[type="hidden"]').remove();
			$('#asqa-filters input[type="checkbox"]').prop('checked', false);
		}
	});

	$(document).ready(function () {
		new SmartQa.views.List();
	});

})(jQuery);