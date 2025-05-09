# Use official PHP image with built-in web server
FROM php:8.2-cli

# Set the working directory
WORKDIR /app

# Copy all project files into the container
COPY . .

# Expose the port that Render expects (10000)
EXPOSE 10000

# Start PHP's built-in development server
CMD ["php", "-S", "0.0.0.0:10000"]
