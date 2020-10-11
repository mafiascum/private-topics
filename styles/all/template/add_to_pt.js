function verify_username(inputElemId, outputElemId, formElemName) {
    const inputElem = $(`#${inputElemId}`);
	const outputElem = $(`#${outputElemId}`);
	
	var endpoint = inputElem.attr('data-endpoint') + '?q=' + inputElem.val();
	
    fetch(endpoint)
        .then(response => response.json())
        .then(data => {
            if (data.length) {
                const user = data[0];
                const userAlreadyExists = outputElem.find(`input[value='${user.user_id}']`);
                if (!userAlreadyExists.length) {
                    outputElem.append([
                        `<li>`,
                        `<input type="button" class="button2" value="x" onclick="splice_user('${outputElemId}', '${user.user_id}')">`,
                        `<input type="hidden" name="${formElemName}[]" value="${user.user_id}">`,
                        `<a href="${user.profile}">${user.username}</a>`,
                        `</li>`
                    ].join(''));
                }
            }
            inputElem.val('');
        });
}

function splice_user(outputElemId, userId) {
    const outputElem = $(`#${outputElemId}`);
    const userToRemove = outputElem.find(`input[value='${userId}']`);
    if (userToRemove) {
        userToRemove.parent().remove();
    }
}