(function ($) {
	SmartQa.views.AskView = Backbone.View.extend({
		initialize: function () {},

		events: {
			'keyup [data-action="suggest_similar_questions"]': 'questionSuggestion'
		},

		suggestTimeout: null,
		questionSuggestion: function (e) {
			var self = this;
			if (disable_q_suggestion || false)
				return;

			var title = $(e.target).val();
			var inputField = this;
			if (title.length == 0)
				return;

			if (self.suggestTimeout != null) clearTimeout(self.suggestTimeout);

			self.suggestTimeout = setTimeout(function () {
				self.suggestTimeout = null;
				SmartQa.ajax({
					data: {
						asqa_ajax_action: 'suggest_similar_questions',
						__nonce: asqa_nonce,
						value: title
					},
					success: function (data) {
						$("#similar_suggestions").remove();
						if(data.html && $("#similar_suggestions").length===0)
							$(e.target).parent().append('<div id="similar_suggestions"></div>');

						$("#similar_suggestions").html(data.html);
					}
				});
			}, 800);
		}
	});

	var askView = new SmartQa.views.AskView({
		el: '#asqa-ask-page'
	});
})(jQuery);