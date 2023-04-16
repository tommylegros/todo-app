document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("todoForm");

    if (form) {
        form.addEventListener("submit", function (event) {
            event.preventDefault();
            const titleInput = document.getElementById("title");
            const completedInput = document.getElementById("completed");
            const title = titleInput.value;
            const completed = completedInput.checked;

            if (title) {
                addTodoItem(title, completed);
            } else {
                alert("Title cannot be empty");
            }
        });
    }
});

function addTodoItem(title, completed) {
    const data = {
        title: title,
        completed: completed,
    };

    fetch("/todos", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error("Failed to create todo item");
            }
            return response.json();
        })
        .then((newTodo) => {
            console.log("New todo item:", newTodo);
            const listItem = document.createElement('li');
            listItem.textContent = data.title;
            document.getElementById('todoList').appendChild(listItem);
            document.getElementById('title').value = '';
        })
        .catch((error) => {
            console.error("Error:", error.message);
        });
}
