/**
 * Javascript code for SmartQa notifications dropdown.
 *
 * @since 4.0.0
 * @package SmartQa
 * @author Peter Mertzlin
 * @license GPL 3+
 */

(function ($) {
	SmartQa.models.Notification = Backbone.Model.extend({
		idAttribute: 'ID',
		defaults: {
			'ID': '',
			'verb': '',
			'verb_label': '',
			'icon': '',
			'avatar': '',
			'hide_actor': '',
			'actor': '',
			'ref_title': '',
			'ref_type': '',
			'points': '',
			'date': '',
			'permalink': '',
			'seen': ''
		}
	});

	SmartQa.collections.Notifications = Backbone.Collection.extend({
		model: SmartQa.models.Notification
	});

	SmartQa.views.Notification = Backbone.View.extend({
		id: function () {
			return 'noti-' + this.model.id;
		},
		template: "<div class=\"noti-item clearfix {{seen==1 ? 'seen' : 'unseen'}}\"><# if(ref_type === 'reputation') { #>  <div class=\"asqa-noti-rep<# if(points < 1) { #> negative<# } #>\">{{points}}</div><# } else if(hide_actor) { #><div class=\"asqa-noti-icon {{icon}}\"></div><# } else { #><div class=\"asqa-noti-avatar\">{{{avatar}}}</div><# } #><a class=\"asqa-noti-inner\" href=\"{{permalink}}\"><# if(ref_type !== 'reputation'){ #><strong class=\"asqa-not-actor\">{{actor}}</strong><# } #> {{verb_label}} <strong class=\"asqa-not-ref\">{{ref_title}}</strong><time class=\"asqa-noti-date\">{{date}}</time></a></div>",
		initialize: function (options) {
			this.model = options.model;
		},
		render: function () {
			var t = _.template(this.template);
			this.$el.html(t(this.model.toJSON()));
			return this;
		}
	});

	SmartQa.views.Notifications = Backbone.View.extend({
		template: "<button class=\"asqa-droptogg apicon-x\"></button><div class=\"asqa-noti-head\">{{aplang.notifications}}<# if(total > 0) { #><i class=\"asqa-noti-unseen\">{{total}}</i><a href=\"#\" class=\"asqa-btn asqa-btn-markall-read asqa-btn-small\" apajaxbtn apquery=\"{{JSON.stringify(mark_args)}}\">{{aplang.mark_all_seen}}</a><# } #></div><div class=\"scroll-wrap\"></div>",
		initialize: function (options) {
			this.model = options.model;
			this.mark_args = options.mark_args;
			this.total = options.total;

			this.listenTo(this.model, 'add', this.newNoti);
			this.listenTo(SmartQa, 'notificationAllRead', this.allRead);
		},
		renderItem: function (notification) {
			var view = new SmartQa.views.Notification({ model: notification });
			this.$el.find('.scroll-wrap').append(view.render().$el);
			return view;
		},
		render: function () {
			var self = this;
			var t = _.template(this.template);
			this.$el.html(t({ 'mark_args': this.mark_args, 'total': this.total }));
			if (this.model.length > 0) {
				this.model.each(function (notification) {
					self.renderItem(notification);
				});
			}

			return this;
		},
		newNoti: function (noti) {
			this.renderItem(noti);
		},
		allRead: function () {
			this.total = 0;
			this.model.each(function (notification) {
				notification.set('seen', 1);
			});
			this.render();
		}
	});

	SmartQa.views.NotiDropdown = Backbone.View.extend({
		id: 'noti-dp',
		initialize: function (options) {
			//this.model = options.model;
			this.anchor = options.anchor;
			this.fetched = false;
		},
		dpPos: function () {
			var pos = this.anchor.offset();
			pos.top = pos.top + this.anchor.height();
			pos.left = pos.left - this.$el.width() + this.anchor.width()
			this.$el.css(pos);
		},
		fetchNoti: function (query, page) {
			if (this.fetched) {
				this.dpPos();
				return;
			}

			var self = this;
			SmartQa.ajax({
				data: ajaxurl + '?action=asqa_ajax&asqa_ajax_action=get_notifications',
				success: function (data) {
					self.fetched = true;
					if (data.success) {
						var notiModel = new SmartQa.collections.Notifications(data.notifications);
						var notificationsView = new SmartQa.views.Notifications({ model: notiModel, mark_args: data.mark_args, total: data.total });
						self.$el.html(notificationsView.render().$el);
						self.dpPos();
						self.$el.show();
					}
				}
			});
		},
		render: function () {
			this.$el.hide();
			return this;
		}
	});

	$(document).ready(function () {
		var anchor = $('a[href="#apNotifications"]');
		var dpView = new SmartQa.views.NotiDropdown({ anchor: anchor });
		$('body').append(dpView.render().$el);

		anchor.on('click', function (e) {
			e.preventDefault();
			dpView.fetchNoti();
			if (dpView.fetched)
				dpView.$el.toggle();
		});

		$(document).on('mouseup', function (e) {
			if (!anchor.is(e.target) && !dpView.$el.is(e.target) && dpView.$el.has(e.target).length === 0) {
				dpView.$el.hide();
			}
		});
	});

})(jQuery);
