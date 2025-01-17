document.addEventListener("DOMContentLoaded", function()
{
    if(typeof article_path === "undefined")
    {
        throw new Error("Article path is not defined prior to loading the add_to_cart.js script.");
    }

    // Add to cart behavior

    let is_submitting = false;

    // Add article to shopping cart
    // TODO: move this function in the footer, so it can be reused

    let shopping_cart_article_template = document.querySelector("header > nav > span.cart > div > template");

    function add_article(article_id, title, price, new_price, discount)
    {
        let cart_clone = shopping_cart_article_template.content.cloneNode(true);

        let article_info = {
            element: cart_clone.querySelector("article"),
            image: cart_clone.querySelector("img"),
            link: cart_clone.querySelector("div.info > a"),
            price: cart_clone.querySelector("div.info > span"),
            quantity: cart_clone.querySelector("div.quantity > span")
        };

        // Set article data-item-id

        article_info.element.dataset.itemId = article_id;

        // Set article image

        article_info.image.alt = "Product image";
        article_info.image.src = `/uploads/articles/${article_id}/large/img1.jpg`;

        // Set article link and name

        article_info.link.href = `/view/${article_info.element.dataset.itemId}`;
        article_info.link.textContent = title;

        // Set article price and discount

        article_info.price.textContent = new_price.toFixed(2) + " " + translate["bulgarian_lev_short"];
        article_info.price.setAttribute("data-price", price);
        article_info.price.setAttribute("data-discount", discount);

        if(discount > 0)
        {
            article_info.price.classList.add("discounted");
        }

        // Set article quantity

        article_info.quantity.textContent = 1;

        // Check if the shopping cart is empty

        if(solve_empty_cart())
        {
            // Remove the empty shopping cart message

            shopping_cart_container.innerHTML = "";

            // Append the article

            shopping_cart_container.appendChild(cart_clone);

            // Add the button

            shopping_cart_container.innerHTML += `<a href="/cart">${translate["view_cart"]}</a>`;
        }
        else
        {
            let existing_article = shopping_cart_container.querySelector(`article[data-item-id="${article_id}"]`);

            // Cart is not empty and the article already exists

            if(existing_article)
            {
                let existing_price = existing_article.querySelector("div.info > span");
                let existing_quantity = existing_article.querySelector("div.quantity > span");

                // Update the quantity and price of the existing article using the current data

                existing_quantity.textContent = parseInt(existing_quantity.textContent) + 1;
                existing_price.textContent = (new_price * parseInt(existing_quantity.textContent)).toFixed(2) + " " + translate["bulgarian_lev_short"];
            }
            else
            {
                shopping_cart_container.insertBefore(cart_clone, shopping_cart_container.lastElementChild);
            }
        }
    }

    // Add to cart button click event

    document.querySelectorAll(article_path).forEach(article => {
        article.querySelector("div > div.buttons > span:last-of-type").addEventListener("click", async () => {
            // Wait for processing of previous attempts

            if(is_submitting)
            {
                return;
            }

            is_submitting = true;

            // Get the article details

            const article_id = article.getAttribute("data-item-id");
            const title = article.querySelector("h1").textContent;

            const price_elem = article.querySelector("div > div.price > span#price");

            let price, new_price;

            if(price_elem.children.length === 0)
            {
                price = price_elem.textContent.replace(/[^0-9.]/g, "");
                new_price = price;
            }
            else
            {
                // First child is actually the new price, while the second one is the old (un-discounted) price

                price = price_elem.children[1].textContent.replace(/[^0-9.]/g, "");
                new_price = price_elem.children[0].textContent.replace(/[^0-9.]/g, "");
            }

            const discount = article.querySelector("span#discount").textContent.replace(/[^0-9.]/g, "");

            const buy_form_data = new FormData();

            // Append the data to the form

            buy_form_data.append("article_id", article_id);
            buy_form_data.append("quantity", 1);

            // Send the form data to the server via AJAX request

            try
            {
                response = await fetch(
                    "/cart/add",
                    {
                        method: "POST",
                        body: buy_form_data
                    }
                );

                const result = await response.json();

                if(result.success)
                {
                    // Update the shopping cart badge

                    // Defined in the footer
                    // let shopping_cart_badge = document.querySelector("header > nav > span.cart > a > span");

                    shopping_cart_badge.textContent = parseInt(shopping_cart_badge.textContent) + 1;

                    // Add the article in the cart

                    add_article(article_id, title, parseFloat(price), parseFloat(new_price), discount);

                    // Display the shopping cart badge (Required when adding artile to empty shopping cart)

                    shopping_cart_badge.style.display = "block";
                }
                else
                {
                    if(result.message === "User not signed in.")
                    {
                        result.message = "[WIP] Currently you must be signed in to buy an article.";
                    }

                    modal_content.textContent = result.message;
                    modal_state.checked = true;
                }
            }
            catch(error)
            {
                modal_content.textContent = "Unexpected error.";
                modal_state.checked = true;
                console.error("Unexpected error: ", error);
            }
            finally
            {
                is_submitting = false;
            }
        });
    });

    // TODO: make the add to favourite button work
});