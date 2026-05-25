const API_URL = 'http://localhost/mycampus-cafe-slim-api/public/api/menu';

const app = Vue.createApp({

    data() {
        return {

            menuItems: [],

            newMenu: {
                menu_name: '',
                category: '',
                price: '',
                availability: 'Available'
            }

        };
    },

    methods: {

        fetchMenu() {

            fetch(API_URL)

            .then(response => response.json())

            .then(data => {
                this.menuItems = data;
            })

            .catch(error => {
                console.error('API error:', error);
            });
        },

        addMenu() {

            fetch(API_URL, {

                method: 'POST',

                headers: {
                    'Content-Type': 'application/json'
                },

                body: JSON.stringify(this.newMenu)

            })

            .then(response => response.json())

            .then(data => {

                this.fetchMenu();

                this.newMenu = {
                    menu_name: '',
                    category: '',
                    price: '',
                    availability: 'Available'
                };

            })

            .catch(error => {
                console.error('Add error:', error);
            });
        },

        deleteMenu(id) {

            fetch(`${API_URL}/${id}`, {

                method: 'DELETE'

            })

            .then(response => response.json())

            .then(data => {
                this.fetchMenu();
            })

            .catch(error => {
                console.error('Delete error:', error);
            });
        },

        updateMenu(item) {

    const updatedName = prompt("Enter new menu name:", item.menu_name);

    if (!updatedName) return;

    fetch(`${API_URL}/${item.menu_id}`, {

        method: 'PUT',

        headers: {
            'Content-Type': 'application/json'
        },

        body: JSON.stringify({
            menu_name: updatedName,
            category: item.category,
            price: item.price,
            availability: item.availability
        })

    })

    .then(response => response.json())

    .then(data => {
        this.fetchMenu();
    })

    .catch(error => {
        console.error('Update error:', error);
    });
}

    },

    mounted() {
        this.fetchMenu();
    }

});

app.mount('#app');