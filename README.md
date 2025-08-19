# url_shortener
A URL shortening service built with Laravel and PostgreSQL. It provides two endpoints: one for shortening a URL and another for redirecting the user to the original URL given a short URL.
## Features 
* **URL Shortening**: Create unique shortened URLs from long URLs, consisting of 7 base-62 characters.
* **Redirection**: Redirects the user to the original long URL given a short URL.
* **404 Handling**: Gracefully handles invalid or non-existent short URLs.
* **Concurrency Handling**: PostgreSQL handles concurrent insertions of the same long URL.
* **Performance Tested**: (locally) read requests load tested with k6 and Laravel Octane.
## Technologies Used
* **Backend**: Laravel
* **Frontend**: Blade
* **Database**: PostgreSQL
* **Testing**: Grafana k6
## Installation & Setup
### Requirements
* Docker
* PHP and Composer
* Laravel
* Octane
### Installation Steps
1. Clone the repository.
2. Install PHP dependencies with `composer install`.
3. Copy `.env.example` and set `DB_USERNAME=postgres` and `DB_PASSWORD` to your own password.
4. At the same level of the `docker-compose.yml` file, create a text files called `db_password`: this should contain the password of your user. 
5. Run `php artisan key:generate`
6. Run `docker compose up` to start PostgreSQL and adminer.
7. Run `php artisan migrate` to setup the database.
8. Run `npm install && npm run dev` to build frontend assets.
9. Run `php artisan octane:start` to start up the development server.
## Endpoints
* `POST /shorten`
	* Returns a short URL given a long URL.
	* Request body (form data): `{"url": "https://www.some-example-url.com"}` 
	* **Response**:  an HTML page with the short URL.
* `GET /{short_url}`
	* Returns a redirect to the original URL (if `short_url` is valid and exists), otherwise returns a 404 page.
## Database Schema
* A single table with a primary key short_url of type varchar(7) and long_url of type text and a unique constraint.
## Short URL Generation
1. A random integer is generated.
2. The integer is hashed using SHA256 algorithm.
3. The first (least significant) 5 bytes are extracted and converted to an integer.
> Note: this step can cause overflow on 32-bit systems. This should be addressed later.
4. The returned integer is encoded as a base-62 string and returned.
5. If the short URL is already used, the process is repeated (this is less likely).
## Handling Concurrent Insertions
* In production, it's possible that multiple users create different short URLs for the same long URL, that is not inserted yet in the database, at the same time. PostgreSQL handles this situation using the `read-committed` consistency level and transactions, where each transaction inserts a temporary row and tries to commit. Only one transaction commits successfully, and the others will fail and rollback, ensuring that the long URL is inserted only once.
* At the application level, this is handled by checking whether the violation was because of the long URL or the short one.
* Long URL uniqueness violation: this means another concurrent transaction just inserted the same long URL, failing this transaction. The corresponding short URL is returned.
* Short URL uniqueness violation: this means that the same short URL was used before for another long URL (collision). The application generates another short URL and retries the insertion.  
## Concurrency & Performance Testing
**Concurrency Testing**: 
* This was done using k6. The test sends concurrent post requests having the same URL, which should not exist in the database. The test checks for the returned status code and the debug messages returned with the response page.
* The number of VUs was varied among 2,10,100,10000, keeping a 1s duration.
* The tests showed that only all VUs return successfully, with only **1** VU inserting the row to the database, and the others indicating a uniqueness violation was handled, returning the existent short url.
* **Load Testing**: (done locally)
* The `GET`endpoint was tested using k6.
* Setup
	* Ramped up to 1200 VUs  over 1m, held for 5m, then cooled down over 1m.
	* URL selection: 75% of the requests used existing valid short URLs, while 25% used random URLs.
* Results
	* Average response time: 8.19 ms
	* p(95) = 18.9 ms
	* All requests returned with 302 (redirection) or 200 (not-found page).
* Given that the test was run locally, these response times are far from those experienced in production environments.
