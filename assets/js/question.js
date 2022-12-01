/**
 * Javascript code for SmartQa fontend
 * @since 4.0.0
 * @package SmartQa
 * @author Peter Mertzlin
 * @license GPL 3+
 */

(function ($) {
	SmartQa.models.Action = Backbone.Model.extend({
		defaults: {
			cb: '',
			post_id: '',
			title: '',
			label: '',
			query: '',
			active: false,
			header: false,
			href: '#',
			count: '',
			prefix: '',
			checkbox: '',
			multiple: false
		}
	});

	SmartQa.collections.Actions = Backbone.Collection.extend({
		model: SmartQa.models.Action
	});

	SmartQa.views.Action = Backbone.View.extend({
		id: function () {
			return this.postID;
		},
		className: function () {
			var klass = '';
			if (this.model.get('header')) klass += ' asqa-dropdown-header';
			if (this.model.get('active')) klass += ' active';
			return klass;
		},
		tagName: 'li',
		template: "<# if(!header){ #><a href=\"{{href}}\" title=\"{{title}}\">{{{prefix}}}{{label}}<# if(count){ #><b>{{count}}</b><# } #></a><# } else { #>{{label}}<# } #>",
		initialize: function (options) {
			this.model = options.model;
			this.postID = options.postID;
			this.model.on('change', this.render, this);
			this.listenTo(this.model, 'remove', this.removed);
		},
		events: {
			'click a': 'triggerAction'
		},
		render: function () {
			var t = _.template(this.template);
			this.$el.html(t(this.model.toJSON()));
			this.$el.attr('class', this.className());
			return this;
		},
		triggerAction: function (e) {
			var q = this.model.get('query');
			if (_.isEmpty(q))
				return;

			e.preventDefault();
			var self = this;
			SmartQa.showLoading(e.target);
			var cb = this.model.get('cb');
			q.asqa_ajax_action = 'action_' + cb;

			SmartQa.ajax({
				data: q,
				success: function (data) {
					SmartQa.hideLoading(e.target);
					if (data.redirect) window.location = data.redirect;

					if (data.success && (cb == 'status' || cb == 'toggle_delete_post'))
						SmartQa.trigger('changedPostStatus', { postID: self.postID, data: data, action: self.model });

					if (data.action) {
						self.model.set(data.action);
					}
					self.renderPostMessage(data);
					if (data.deletePost) SmartQa.trigger('deletePost', data.deletePost);
					if (data.answersCount) SmartQa.trigger('answerCountUpdated', data.answersCount);
				}
			});
		},
		renderPostMessage: function (data) {
			if (!_.isEmpty(data.postmessage))
				$('[apid="' + this.postID + '"]').find('postmessage').html(data.postmessage);
			else
				$('[apid="' + this.postID + '"]').find('postmessage').html('');
		},
		removed: function () {
			this.remove();
		}
	});


	SmartQa.views.Actions = Backbone.View.extend({
		id: function () {
			return this.postID;
		},
		searchTemplate: '<div class="asqa-filter-search"><input type="text" search-filter placeholder="' + aplang.search + '" /></div>',
		tagName: 'ul',
		className: 'asqa-actions',
		events: {
			'keyup [search-filter]': 'searchInput'
		},
		initialize: function (options) {
			this.model = options.model;
			this.postID = options.postID;
			this.multiple = options.multiple;
			this.action = options.action;
			this.nonce = options.nonce;

			SmartQa.on('changedPostStatus', this.postStatusChanged, this);
			this.listenTo(this.model, 'add', this.added);
		},
		renderItem: function (action) {
			var view = new SmartQa.views.Action({ model: action, postID: this.postID });
			this.$el.append(view.render().$el);
		},
		render: function () {
			var self = this;
			if (this.multiple)
				this.$el.append(this.searchTemplate);

			this.model.each(function (action) {
				self.renderItem(action);
			});

			return this;
		},
		postStatusChanged: function (args) {
			if (args.postID !== this.postID) return;

			// Remove post status class
			$("#post-" + this.postID).removeClass(function () {
				return this.className.split(' ').filter(function (className) { return className.match(/status-/) }).join(' ');
			});

			$("#post-" + this.postID).addClass('status-' + args.data.newStatus);
			var activeStatus = this.model.where({ cb: 'status', active: true });

			activeStatus.forEach(function (status) {
				status.set({ active: false });
			});
		},
		searchInput: function (e) {
			var self = this;

			clearTimeout(this.searchTO);
			this.searchTO = setTimeout(function () {
				self.search($(e.target).val(), e.target);
			}, 600);
		},
		search: function (q, e) {
			var self = this;

			var args = { nonce: this.nonce, asqa_ajax_action: this.action, search: q, filter: this.filter, post_id: this.postID };

			SmartQa.showLoading(e);
			SmartQa.ajax({
				data: args,
				success: function (data) {
					console.log(data);
					SmartQa.hideLoading(e);
					if (data.success) {
						self.nonce = data.nonce;
						//self.model.reset();
						while (m = self.model.first()) {
							self.model.remove(m);
						}
						self.model.add(data.actions);
					}
				}
			});
		},
		added: function (model) {
			this.renderItem(model);
		}
	});

	SmartQa.models.Post = Backbone.Model.extend({
		idAttribute: 'ID',
		defaults: {
			actionsLoaded: false,
			hideSelect: ''
		}
	});

	SmartQa.views.Post = Backbone.View.extend({
		idAttribute: 'ID',
		templateId: 'answer',
		tagName: 'div',
		actions: { view: {}, model: {} },
		id: function () {
			return 'post-' + this.model.get('ID');
		},
		initialize: function (options) {
			this.listenTo(this.model, 'change:vote', this.voteUpdate);
			this.listenTo(this.model, 'change:hideSelect', this.selectToggle);
		},
		events: {
			'click [asqa-vote] > a': 'voteClicked',
			'click [ap="actiontoggle"]:not(.loaded)': 'postActions',
			'click [ap="select_answer"]': 'selectAnswer'
		},
		voteClicked: function (e) {
			e.preventDefault();
			if ($(e.target).is('.disable'))
				return;

			self = this;
			var type = $(e.target).is('.vote-up') ? 'vote_up' : 'vote_down';
			var originalValue = _.clone(self.model.get('vote'));
			var vote = _.clone(originalValue);

			if (type === 'vote_up')
				vote.net = (vote.active === 'vote_up' ? vote.net - 1 : vote.net + 1);
			else
				vote.net = (vote.active === 'vote_down' ? vote.net + 1 : vote.net - 1);

			self.model.set('vote', vote);
			var q = JSON.parse($(e.target).parent().attr('asqa-vote'));
			q.asqa_ajax_action = 'vote';
			q.type = type;

			SmartQa.ajax({
				data: q,
				success: function (data) {
					if (data.success && _.isObject(data.voteData))
						self.model.set('vote', data.voteData);
					else
						self.model.set('vote', originalValue); // Restore original value on fail
				}
			})
		},
		voteUpdate: function (post) {
			var self = this;
			this.$el.find('[ap="votes_net"]').text(this.model.get('vote').net);
			_.each(['up', 'down'], function (e) {
				self.$el.find('.vote-' + e).removeClass('voted disable').addClass(self.voteClass('vote_' + e));
			});
		},
		voteClass: function (type) {
			type = type || 'vote_up';
			var curr = this.model.get('vote').active;
			var klass = '';
			if (curr === 'vote_up' && type === 'vote_up')
				klass = 'active';

			if (curr === 'vote_down' && type === 'vote_down')
				klass = 'active';

			if (type !== curr && curr !== '')
				klass += ' disable';

			return klass + ' prist';
		},
		render: function () {
			var attr = this.$el.find('[asqa-vote]').attr('asqa-vote');
			try {
				this.model.set('vote', JSON.parse(attr), { silent: true });
			} catch (err) {
				console.warn('Vote data empty', err)
			}
			return this;
		},
		postActions: function (e) {
			var self = this;
			var q = JSON.parse($(e.target).attr('apquery'));
			if (typeof q.asqa_ajax_action === 'undefined')
				q.asqa_ajax_action = 'post_actions';

			SmartQa.ajax({
				data: q,
				success: function (data) {
					SmartQa.hideLoading(e.target);
					$(e.target).addClass('loaded');
					self.actions.model = new SmartQa.collections.Actions(data.actions);
					self.actions.view = new SmartQa.views.Actions({ model: self.actions.model, postID: self.model.get('ID') });
					self.$el.find('postActions .asqa-actions').html(self.actions.view.render().$el);
				}
			});
		},

		selectAnswer: function (e) {
			e.preventDefault();
			var self = this;
			var q = JSON.parse($(e.target).attr('apquery'));
			q.action = 'asqa_toggle_best_answer';

			SmartQa.showLoading(e.target);
			SmartQa.ajax({
				data: q,
				success: function (data) {
					SmartQa.hideLoading(e.target);
					if (data.success) {
						if (data.selected) {
							self.$el.addClass('best-answer');
							$(e.target).addClass('active').text(data.label);
							SmartQa.trigger('answerToggle', [self.model, true]);
						} else {
							self.$el.removeClass('best-answer');
							$(e.target).removeClass('active').text(data.label);
							SmartQa.trigger('answerToggle', [self.model, false]);
						}
					}
				}
			});
		},
		selectToggle: function () {
			if (this.model.get('hideSelect'))
				this.$el.find('[ap="select_answer"]').addClass('hide');
			else
				this.$el.find('[ap="select_answer"]').removeClass('hide');
		}
	});

	SmartQa.collections.Posts = Backbone.Collection.extend({
		model: SmartQa.models.Post,
		initialize: function () {
			var loadedPosts = [];
			$('[ap="question"],[ap="answer"]').each(function (e) {
				loadedPosts.push({ 'ID': $(this).attr('apId') });
			});
			this.add(loadedPosts);
		}
	});

	SmartQa.views.SingleQuestion = Backbone.View.extend({
		initialize: function () {
			this.listenTo(this.model, 'add', this.renderItem);
			SmartQa.on('answerToggle', this.answerToggle, this);
			SmartQa.on('deletePost', this.deletePost, this);
			SmartQa.on('answerCountUpdated', this.answerCountUpdated, this);
			SmartQa.on('formPosted', this.formPosted, this);
			this.listenTo(SmartQa, 'commentApproved', this.commentApproved);
			this.listenTo(SmartQa, 'commentDeleted', this.commentDeleted);
			this.listenTo(SmartQa, 'commentCount', this.commentCount);
			this.listenTo(SmartQa, 'formPosted', this.submitComment);
		},
		events: {
			'click [ap="loadEditor"]': 'loadEditor',
		},
		renderItem: function (post) {
			var view = new SmartQa.views.Post({ model: post, el: '[apId="' + post.get('ID') + '"]' });
			view.render();
		},

		render: function () {
			var self = this;
			this.model.each(function (post) {
				self.renderItem(post);
			});

			return self;
		},

		loadEditor: function (e) {
			var self = this;
			SmartQa.showLoading(e.target);

			SmartQa.ajax({
				data: $(e.target).data('apquery'),
				success: function (data) {
					console.log(data)
					SmartQa.hideLoading(e.target);
					$('#asqa-form-main').html(data);
					$(e.target).closest('.asqa-minimal-editor').removeClass('asqa-minimal-editor');
				}
			});
		},
		/**
		 * Handles answer form submission.
		 */
		formPosted: function (data) {
			if (data.success && data.form === 'answer') {
				SmartQa.trigger('answerFormPosted', data);
				$('apanswersw').show();
				tinymce.remove();

				// Clear editor contents
				$('#asqa-form-main').html('');
				$('#answer-form-c').addClass('asqa-minimal-editor');

				// Append answer to the list.
				$('apanswers').append($(data.html).hide());
				$(data.div_id).slideDown(300);
				$(data.div_id).apScrollTo(null, true);
				this.model.add({ 'ID': data.ID });
				SmartQa.trigger('answerCountUpdated', data.answersCount);
			}
		},
		answerToggle: function (args) {
			this.model.forEach(function (m, i) {
				if (args[0] !== m)
					m.set('hideSelect', args[1]);
			});
		},
		deletePost: function (postID) {
			this.model.remove(postID);
			$('#post-' + postID).slideUp(400, function () {
				$(this).remove();
			});
		},
		answerCountUpdated: function (counts) {
			$('[ap="answers_count_t"]').text(counts.text);
		},
		commentApproved: function (data, elm) {
			$('#comment-' + data.comment_ID).removeClass('unapproved');
			$(elm).remove();
			if (data.commentsCount)
				SmartQa.trigger('commentCount', { count: data.commentsCount, postID: data.post_ID });
		},
		commentDeleted: function (data, elm) {
			$(elm).closest('apcomment').css('background', 'red');
			setTimeout(function () {
				$(elm).closest('apcomment').remove();
			}, 1000);
			if (data.commentsCount)
				SmartQa.trigger('commentCount', { count: data.commentsCount, postID: data.post_ID });
		},
		commentCount: function (args) {
			var find = $('[apid="' + args.postID + '"]');
			find.find('[asqa-commentscount-text]').text(args.count.text);
			if (args.count.unapproved > 0)
				find.find('[asqa-un-commentscount]').addClass('have');
			else
				find.find('[asqa-un-commentscount]').removeClass('have');

			find.find('[asqa-un-commentscount]').text(args.count.unapproved);
		},
		submitComment: function (data) {
			if (!('new-comment' !== data.action || 'edit-comment' !== data.action))
				return;

			if (data.success) {
				SmartQa.hideModal('commentForm');
				if (data.action === 'new-comment')
					$('#comments-' + data.post_id).html(data.html);

				if (data.action === 'edit-comment') {
					$old = $('#comment-' + data.comment_id);
					$(data.html).insertAfter($old);
					$old.remove();

					$('#comment-' + data.comment_id).css('backgroundColor', 'rgba(255, 235, 59, 1)');
					setTimeout(function () {
						$('#comment-' + data.comment_id).removeAttr('style');
					}, 500)
				}

				if (data.commentsCount)
					SmartQa.trigger('commentCount', { count: data.commentsCount, postID: data.post_id });
			}
		}
	});

	var SmartQaRouter = Backbone.Router.extend({
		routes: {
			'comment/:commentID': 'commentRoute',
			//'comment/:commentID/edit': 'editCommentsRoute',
			'comments/:postID/all': 'commentsRoute',
			'comments/:postID': 'commentsRoute',
		},
		commentRoute: function (commentID) {
			self = this;

			SmartQa.hideModal('comment', false);
			$modal = SmartQa.modal('comment', {
				content: '',
				size: 'medium',
				hideCb: function () {
					SmartQa.removeHash();
				}
			});
			$modal.$el.addClass('single-comment');
			SmartQa.showLoading($modal.$el.find('.asqa-modal-content'));
			SmartQa.ajax({
				data: { comment_id: commentID, asqa_ajax_action: 'load_comments' },
				success: function (data) {
					if (data.success) {
						$modal.setTitle(data.modal_title);
						$modal.setContent(data.html);
						SmartQa.hideLoading($modal.$el.find('.asqa-modal-content'));
					}
				}
			});
		},

		commentsRoute: function (postId, paged) {
			self = this;
			SmartQa.ajax({
				data: { post_id: postId, asqa_ajax_action: 'load_comments' },
				success: function (data) {
					$('#comments-' + postId).html(data.html);
				}
			});
		},
		editCommentsRoute: function (commentID) {
			self = this;
			SmartQa.hideModal('commentForm', false);
			SmartQa.modal('commentForm', {
				hideCb: function () {
					SmartQa.removeHash();
				}
			});

			SmartQa.showLoading(SmartQa.modal('commentForm').$el.find('.asqa-modal-content'));
			SmartQa.ajax({
				data: { comment: commentID, asqa_ajax_action: 'comment_form' },
				success: function (data) {
					SmartQa.hideLoading(SmartQa.modal('commentForm').$el.find('.asqa-modal-content'));
					SmartQa.modal('commentForm').setTitle(data.modal_title);
					SmartQa.modal('commentForm').setContent(data.html);
				}
			});
		}
	});

	$('[ap="actiontoggle"]').on('click', function () {
		if (!$(this).is('.loaded'))
			SmartQa.showLoading(this);
	});

	$(document).ready(function () {
		var apposts = new SmartQa.collections.Posts();
		var singleQuestionView = new SmartQa.views.SingleQuestion({ model: apposts, el: '#smartqa' });
		singleQuestionView.render();

		var smartqaRouter = new SmartQaRouter();
		if (!Backbone.History.started)
			Backbone.history.start();
	});


})(jQuery);
