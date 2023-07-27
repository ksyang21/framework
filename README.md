# Framework

This is a minimal PHP framework that provides a basic understanding of the Model-View-Controller (MVC) architecture. It is designed to help you grasp the fundamental concepts of building web applications using this popular architectural pattern.

## WIP
- [ ] Render view
- [x] Route to call controller function
- [ ] Middleware
- [ ] Route with callback function
- [ ] Shared data from middleware
- [ ] Sanitize inputs
- [ ] Define fillables for data input
- [ ] More to be added...

## Features

- **MVC Architecture**: The framework follows the Model-View-Controller architectural pattern, which separates application logic, data, and presentation.

- **Routing**: The framework includes a simple routing mechanism to map URLs to controller actions.

- **Controllers**: Controllers handle user requests, process data, and interact with models and views.

- **Models**: Models represent the data and business logic of the application.

- **Views**: Views are responsible for presenting data to users in a user-friendly format.

## Getting Started

### Requirements

- PHP (version 7.2 or higher)

### Installation

1. Clone the repository: `git clone https://github.com/ksyang1121/framework.git`
2. Change into the project directory: `cd framework`
3. Start the built-in PHP development server: `php -S localhost:8000`

### Configuration

The framework does not require any specific configuration. However, you can customize the routing rules in the `api.php` file to add new routes and map them to controller actions.

## License

This project is licensed under the [MIT License](LICENSE). Feel free to use, modify, and distribute it as you see fit.
