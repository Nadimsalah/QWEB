# QOON Full API Documentation

## 📁 User

### 🔹 `AcceptOfferUser.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`
- `DelvryId`
- `OrderPrice`
- `OfferKey`

---
### 🔹 `AddAddressUser.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `AddressType`
- `AddressText`
- `AddressLat`
- `AddressLongt`
- `AddressName`

---
### 🔹 `AddChagreToUser.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `Money`
- `ReceiverID`

---
### 🔹 `AddChargeToUser.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `Money`
- `ReceiverID`

---
### 🔹 `AddFundUser.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `Money`

---
### 🔹 `CancelOrderUser.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`

---
### 🔹 `ChangeLangUser.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `LANG`

---
### 🔹 `ChangeProUser.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `Pro`

---
### 🔹 `CheckCodeUser.php`
**Method:** `POST`

**POST Parameters:**
- `UserPhone`
- `UserFirebaseToken`
- `Code`

---
### 🔹 `CompleteUserAfterRegister.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `fullname`
- `PhoneNumber`
- `Gender`
- `BirthDate`
- `CityID`
- `PersonalPhoto`

**FILE Parameters:**
- `photo` (File)

---
### 🔹 `DeleteUserForApple.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`

---
### 🔹 `GetAllServiceReviewUser.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`

---
### 🔹 `GetFeesJiblerUser.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`

---
### 🔹 `GetFeesJiblerUserPrice.php`
**Method:** `POST`

**POST Parameters:**
- `total`

---
### 🔹 `GetUserBalance.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`

---
### 🔹 `GetUserByScanID.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`

---
### 🔹 `GetUserInformation.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`

---
### 🔹 `GetUserOrders.php`
**Method:** `POST`

**POST Parameters:**
- `UserId`

---
### 🔹 `GetUserOrdersHistory.php`
**Method:** `POST`

**POST Parameters:**
- `UserId`

---
### 🔹 `GetUserTransaction.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`

---
### 🔹 `GetUsersByWritePhone.php`
**Method:** `POST`

**POST Parameters:**
- `PhoneNumber`

---
### 🔹 `LogoutUserApi.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`

---
### 🔹 `SendCodeUser.php`
**Method:** `POST`

**POST Parameters:**
- `PhoneNumber`
- `Code`

---
### 🔹 `UpdateProfileImageUserinapp.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `userId`
- `fullname`
- `FullName`
- `email`
- `Email`
- `PhoneNumber`
- `CityID`
- `PersonalPhoto`
- `Photo`

**FILE Parameters:**
- `photo` (File)

---
### 🔹 `UpdateProfileUserWithImage.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `userId`
- `fullname`
- `FullName`
- `email`
- `Email`
- `PhoneNumber`
- `CityID`
- `PersonalPhoto`
- `Photo`

**FILE Parameters:**
- `photo` (File)

---
### 🔹 `UpdateProfileUserWithImageinapp.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `userId`
- `fullname`
- `FullName`
- `email`
- `Email`
- `PhoneNumber`
- `CityID`
- `PersonalPhoto`
- `Photo`

**FILE Parameters:**
- `photo` (File)

---
### 🔹 `UpdateProfileUserWithoutImage (1).php`
**Method:** `POST`

**POST Parameters:**
- `UserPhone`
- `AccountType`
- `FaceID`
- `GoogleID`
- `FullName`
- `Email`
- `BirthOfDate`
- `Gender`

---
### 🔹 `UpdateProfileUserWithoutImage.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `fullname`
- `email`
- `PhoneNumber`
- `CityID`

---
### 🔹 `UpdateProfileUserWithoutImageinapp (1).php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `fullname`
- `email`

---
### 🔹 `UpdateProfileUserWithoutImageinapp.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `fullname`
- `email`
- `PhoneNumber`
- `CityID`

---
### 🔹 `UpdateUserPhoto.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `userId`
- `fullname`
- `FullName`
- `email`
- `Email`
- `PhoneNumber`
- `CityID`
- `PersonalPhoto`
- `Photo`

**FILE Parameters:**
- `photo` (File)

---
### 🔹 `UserSendMessage.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`
- `DriverID`
- `message`

---
### 🔹 `UserSendMessageToUser.php`
**Method:** `POST`

**POST Parameters:**
- `SenderID`
- `UserID`
- `messsage`

---
### 🔹 `getAllUserTstForNot.php`
**Method:** `GET`

---
### 🔹 `getUserNotification.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`

---
### 🔹 `getuserNotificationnotseennum.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`

---
### 🔹 `upload_user_photo.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `userId`
- `fullname`
- `FullName`
- `email`
- `Email`
- `PhoneNumber`
- `CityID`
- `PersonalPhoto`
- `Photo`

**FILE Parameters:**
- `photo` (File)

---
### 🔹 `user-profile.php`
**Method:** `GET`

**GET Parameters:**
- `iframe`

---
## 📁 Posts & Comments

### 🔹 `AddComment.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `ShopID`
- `CommentText`
- `PostID`

---
### 🔹 `AddLike.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `ShopID`
- `PostID`

---
### 🔹 `AddReportPost.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `Text`
- `PostId`

---
### 🔹 `DeleteComment.php`
**Method:** `POST`

**POST Parameters:**
- `CommentID`

---
### 🔹 `GetAllPosts.php`
**Method:** `POST`

**POST Parameters:**
- `UserLat`
- `UserLongt`
- `UserID`
- `Page`
- `Pro`

---
### 🔹 `GetAllPostsById.php`
**Method:** `POST`

**POST Parameters:**
- `UserLat`
- `UserLongt`
- `UserID`
- `PostID`
- `Page`

---
### 🔹 `GetAllPostsById_debug.php`
**Method:** `POST`

**POST Parameters:**
- `UserLat`
- `UserLongt`
- `UserID`
- `PostID`
- `Page`

---
### 🔹 `GetAllPostsById_debug2.php`
**Method:** `POST`

**POST Parameters:**
- `UserLat`
- `UserLongt`
- `UserID`
- `PostID`
- `Page`

---
### 🔹 `GetAllPostsLiked.php`
**Method:** `POST`

**POST Parameters:**
- `UserLat`
- `UserLongt`
- `UserID`
- `Page`

---
### 🔹 `GetAllPosts_LiveFix.php`
**Method:** `POST`

**POST Parameters:**
- `UserLat`
- `UserLongt`
- `UserID`
- `Page`
- `Pro`

---
### 🔹 `GetPostCommentsWeb.php`
**Method:** `POST`

**POST Parameters:**
- `PostID`

---
### 🔹 `GetPostCommentss (1).php`
**Method:** `POST`

**POST Parameters:**
- `PostID`

---
### 🔹 `GetPostCommentss.php`
**Method:** `POST`

**POST Parameters:**
- `PostID`

---
### 🔹 `GetReelsApi.php`
**Method:** `GET`

---
### 🔹 `RemoveLike.php`
**Method:** `POST`

**POST Parameters:**
- `PostID`
- `UserID`

---
### 🔹 `category_reels.php`
**Method:** `GET`

**GET Parameters:**
- `cat`
- `id`
- `type`

---
### 🔹 `getReelsRecommendationAPI.php`
**Method:** `GET`

**GET Parameters:**
- `user_id`
- `limit`
- `offset`

---
## 📁 Shop

### 🔹 `AddFundShop.php`
**Method:** `POST`

**POST Parameters:**
- `ShopID`
- `Money`
- `ShopTransactionID`

---
### 🔹 `CancelOrderShop.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`
- `CancelOrderReasonsID`

---
### 🔹 `ChangeBakaShop.php`
**Method:** `POST`

**POST Parameters:**
- `ShopID`
- `BakatID`

---
### 🔹 `CheckIfShopFollowed.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `ShopID`

---
### 🔹 `GetAllCategoriesInShop.php`
**Method:** `POST`

**POST Parameters:**
- `ShopID`

---
### 🔹 `GetAllCategoriesInShopInMainCategory.php`
**Method:** `POST`

**POST Parameters:**
- `CategoryId`
- `UserLat`
- `UserLongt`

---
### 🔹 `GetAllMenusByShop.php`
**Method:** `POST`

**POST Parameters:**
- `ShopID`

---
### 🔹 `GetAllPostsFollowedShop.php`
**Method:** `POST`

**POST Parameters:**
- `UserLat`
- `UserLongt`
- `UserID`

---
### 🔹 `GetAllPostsForShop.php`
**Method:** `POST`

**POST Parameters:**
- `UserLat`
- `UserLongt`
- `UserID`
- `ShopID`
- `Page`

---
### 🔹 `GetAllShopByCategory.php`
**Method:** `POST`

**POST Parameters:**
- `CategoryID`
- `UserLat`
- `UserLongt`
- `Page`
- `KeyWord`
- `SearchWord`
- `UserID`

---
### 🔹 `GetAllShopByCategoryHasStories.php`
**Method:** `POST`

**POST Parameters:**
- `CategoryID`
- `UserLat`
- `UserLongt`
- `UserID`
- `SearchWord`

---
### 🔹 `GetAllShopByCategoryNotthisShop.php`
**Method:** `POST`

**POST Parameters:**
- `CategoryId`
- `UserLat`
- `UserLongt`
- `Page`
- `KeyWord`
- `UserID`
- `ShopID`

---
### 🔹 `GetAllShopByCategoryOur.php`
**Method:** `POST`

**POST Parameters:**
- `CategoryID`
- `UserLat`
- `UserLongt`
- `Page`
- `SearchWord`
- `UserID`

---
### 🔹 `GetAllShopByCategoryOurFiltred.php`
**Method:** `POST`

**POST Parameters:**
- `CategoryID`
- `UserLat`
- `UserLongt`
- `Page`
- `UserID`
- `SearchWord`
- `KinzMadintySmallProductsID`

---
### 🔹 `GetAllShopByCategoryOurNotME.php`
**Method:** `POST`

**POST Parameters:**
- `CategoryID`
- `UserLat`
- `UserLongt`
- `Page`
- `UserID`
- `ShopID`

---
### 🔹 `GetAllShopByINHOME.php`
**Method:** `POST`

**POST Parameters:**
- `CategoryID`
- `UserLat`
- `UserLongt`
- `UserID`

---
### 🔹 `GetAllShopByWord.php`
**Method:** `POST`

**POST Parameters:**
- `search`
- `UserLat`
- `UserLongt`
- `Page`
- `UserID`

---
### 🔹 `GetAllShopHasStories.php`
**Method:** `POST`

**POST Parameters:**
- `CategoryID`
- `UserLat`
- `UserLongt`
- `UserID`

---
### 🔹 `GetAllShopHasStoriesFollowed.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `UserLat`
- `UserLongt`

---
### 🔹 `GetAllStoriesOfShop.php`
**Method:** `POST`

**POST Parameters:**
- `ShopID`

---
### 🔹 `GetAllSuggestedShop.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `UserLat`
- `UserLongt`

---
### 🔹 `GetDelvPriceForShop.php`
**Method:** `POST`

**POST Parameters:**
- `ShopID`

---
### 🔹 `GetOrderShopType.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`

---
### 🔹 `GetShop.php`
**Method:** `POST`

**POST Parameters:**
- `ShopID`
- `UserLat`
- `UserLongt`

---
### 🔹 `GetShopBalance.php`
**Method:** `POST`

**POST Parameters:**
- `ShopID`

---
### 🔹 `GetShopByPhones.php`
**Method:** `POST`

**POST Parameters:**
- `PhoneNumber`
- `ShopFirebaseToken`

---
### 🔹 `GetShopTransaction.php`
**Method:** `POST`

**POST Parameters:**
- `ShopID`
- `Type`

---
### 🔹 `GetUsersWhoRateShop.php`
**Method:** `POST`

**POST Parameters:**
- `ShopID`

---
### 🔹 `LoginShop.php`
**Method:** `POST`

**POST Parameters:**
- `ShopLogName`
- `ShopPassword`
- `ShopFirebaseToken`

---
### 🔹 `category_shops.php`
**Method:** `GET`

**GET Parameters:**
- `cat`

---
## 📁 General

### 🔹 `AddOrDeleteFollow.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `ShopID`

---
### 🔹 `AddProductToStoryAfterUpload.php`
**Method:** `POST`

**POST Parameters:**
- `StoryID`
- `ProductId`

---
### 🔹 `ChangeBoostStatus.php`
**Method:** `POST`

**POST Parameters:**
- `BoostStatus`
- `BoostsByShopID`

---
### 🔹 `ChangeStoryStatus.php`
**Method:** `GET`

**GET Parameters:**
- `PostId`
- `StoryStatus`

---
### 🔹 `CheckBalance.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `Total`
- `OrderID`
- `Method`

---
### 🔹 `CheckOTP.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `sentcode`

---
### 🔹 `CloseRate.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`

---
### 🔹 `Confrmid.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`

---
### 🔹 `DeleteFile.php`
**Method:** `POST`

**POST Parameters:**
- `RequestUserFilesID`

---
### 🔹 `GetAllAddress.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`

---
### 🔹 `GetAllCategories.php`
**Method:** `POST`

**POST Parameters:**
- `UserLat`
- `UserLongt`
- `Pro`
- `Page`

---
### 🔹 `GetAllCities.php`
**Method:** `POST`

**POST Parameters:**
- `CountryID`
- `UserLat`
- `UserLongt`

---
### 🔹 `GetAllFollowing (1).php`
**Method:** `POST`

**POST Parameters:**
- `UserID`

---
### 🔹 `GetAllFollowing.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`

---
### 🔹 `GetAllFoodbyCat.php`
**Method:** `POST`

**POST Parameters:**
- `FoodCatID`

---
### 🔹 `GetAllFoodbyOfferToday.php`
**Method:** `POST`

**POST Parameters:**
- `CategoryId`
- `UserLat`
- `UserLongt`
- `SearchWord`
- `KinzMadintySmallProductsID`

---
### 🔹 `GetAllSetting.php`
**Method:** `GET`

---
### 🔹 `GetAllSlider.php`
**Method:** `POST`

**POST Parameters:**
- `CategoryID`
- `UserLat`
- `UserLongt`

---
### 🔹 `GetAllSmallCategories.php`
**Method:** `POST`

**POST Parameters:**
- `Pro`

---
### 🔹 `GetAllStories.php`
**Method:** `GET`

---
### 🔹 `GetAllVideosReals.php`
**Method:** `POST`

**POST Parameters:**
- `UserLat`
- `UserLongt`
- `UserID`
- `Page`

---
### 🔹 `GetAllVideosRealsById.php`
**Method:** `POST`

**POST Parameters:**
- `PostID`
- `UserLat`
- `UserLongt`
- `UserID`
- `Page`

---
### 🔹 `GetAllVideosRealsFav.php`
**Method:** `POST`

**POST Parameters:**
- `UserLat`
- `UserLongt`
- `UserID`
- `Page`

---
### 🔹 `GetCashPlusBranches.php`
**Method:** `POST`

**POST Parameters:**
- `UserLat`
- `UserLongt`

---
### 🔹 `GetKinzMadinty.php`
**Method:** `POST`

**POST Parameters:**
- `CategoryID`
- `UserLat`
- `UserLongt`
- `Page`
- `KeyWord`
- `UserID`

---
### 🔹 `GetKinzMadintyInHome.php`
**Method:** `POST`

**POST Parameters:**
- `CategoryID`
- `UserLat`
- `UserLongt`
- `Page`
- `KeyWord`
- `UserID`

---
### 🔹 `GetKinzMadintyInHomeHasStory.php`
**Method:** `POST`

**POST Parameters:**
- `CategoryID`
- `UserLat`
- `UserLongt`
- `Page`
- `KeyWord`
- `UserID`

---
### 🔹 `GetKinzMadintyInHomeHasStoryInCategory.php`
**Method:** `POST`

**POST Parameters:**
- `CategoryID`
- `UserLat`
- `UserLongt`
- `Page`
- `KeyWord`
- `UserID`

---
### 🔹 `GetKinzMadintyInHomeMadinty.php`
**Method:** `POST`

**POST Parameters:**
- `CategoryID`
- `UserLat`
- `UserLongt`
- `Page`
- `KeyWord`
- `UserID`

---
### 🔹 `GetKinzMadintyInHomeMadintyInCategory.php`
**Method:** `POST`

**POST Parameters:**
- `CategoryID`
- `UserLat`
- `UserLongt`
- `Page`
- `KeyWord`
- `UserID`

---
### 🔹 `GetKinzProducts.php`
**Method:** `POST`

**POST Parameters:**
- `CategoryID`

---
### 🔹 `GetKinzProductsBySmallCategory.php`
**Method:** `POST`

**POST Parameters:**
- `KinzMadintySmallProductsID`

---
### 🔹 `GetMyPatientsFiles (1).php`
**Method:** `POST`

**POST Parameters:**
- `PatientID`

---
### 🔹 `GetMyPatientsFiles.php`
**Method:** `POST`

**POST Parameters:**
- `PatientID`

---
### 🔹 `GetPro.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`

---
### 🔹 `GetProdColor.php`
**Method:** `POST`

**POST Parameters:**
- `FoodID`

---
### 🔹 `GetProdSizes.php`
**Method:** `POST`

**POST Parameters:**
- `FoodID`

---
### 🔹 `GetSliderNotHome.php`
**Method:** `POST`

**POST Parameters:**
- `CategoryID`
- `UserLat`
- `UserLongt`

---
### 🔹 `GetVersionCode.php`
**Method:** `GET`

---
### 🔹 `Getexpressphoto.php`
**Method:** `POST`

**POST Parameters:**
- `DriverID`

---
### 🔹 `IgiveUProducts.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`

---
### 🔹 `NanoBananaApi.php`
**Method:** `POST`

**GET Parameters:**
- `action`
- `taskId`

**POST Parameters:**
- `action`
- `url`
- `userImg`
- `prodImg`
- `prompt`

**FILE Parameters:**
- `file` (File)

---
### 🔹 `Picked.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`
- `AppType`

---
### 🔹 `Proccesed.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`

---
### 🔹 `PrunaAIApi.php`
**Method:** `POST`

**POST Parameters:**
- `action`
- `userImg`
- `prodImg`

**FILE Parameters:**
- `file` (File)

---
### 🔹 `Take.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`

---
### 🔹 `UpdateAndoidOrIphone.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `UserType`

---
### 🔹 `WhatsappAPI.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `PhoneNumber`

---
### 🔹 `addoffer.php`
**Method:** `POST`

**POST Parameters:**
- `DriverID`
- `driverId`
- `OrderId`
- `orderId`
- `Price`
- `price`
- `Offer`
- `offer`
- `OrderType`
- `AppType`

---
### 🔹 `ajax_global_products.php`
**Method:** `GET`

**GET Parameters:**
- `page`

---
### 🔹 `aliexpress_auth.php`
**Method:** `GET`

**GET Parameters:**
- `code`

---
### 🔹 `buy_esim.php`
**Method:** `GET`

---
### 🔹 `chat.php`
**Method:** `GET`

**GET Parameters:**
- `iframe`
- `phone`

---
### 🔹 `check_esim.php`
**Method:** `GET`

**GET Parameters:**
- `transactionId`

---
### 🔹 `check_time.php`
**Method:** `GET`

---
### 🔹 `config.php`
**Method:** `GET`

---
### 🔹 `confirmPayPartner.php`
**Method:** `POST`

**POST Parameters:**
- `DriverID`

---
### 🔹 `conn_debug.php`
**Method:** `GET`

---
### 🔹 `debug_clone.php`
**Method:** `GET`

---
### 🔹 `debug_last_vto.php`
**Method:** `GET`

---
### 🔹 `delete_product_temp.php`
**Method:** `GET`

**GET Parameters:**
- `confirm`

---
### 🔹 `delivery_offers.php`
**Method:** `POST`

**GET Parameters:**
- `ajax`
- `orderId`
- `total`

**POST Parameters:**
- `shopId`
- `shopName`
- `addrLat`
- `addrLon`
- `cart`

---
### 🔹 `esimaccess_countries.php`
**Method:** `GET`

---
### 🔹 `fix_dash_photos.php`
**Method:** `GET`

---
### 🔹 `fix_json.php`
**Method:** `GET`

---
### 🔹 `fix_latlong.php`
**Method:** `GET`

---
### 🔹 `fix_latlong2.php`
**Method:** `POST`

**POST Parameters:**
- `UserLat`
- `UserLongt`

---
### 🔹 `fix_latlong3.php`
**Method:** `POST`

**POST Parameters:**
- `UserLat`
- `UserLongt`

---
### 🔹 `fix_latlong4.php`
**Method:** `POST`

**POST Parameters:**
- `UserLat`
- `UserLongt`

---
### 🔹 `fix_pruna_output.php`
**Method:** `GET`

---
### 🔹 `fix_upload.php`
**Method:** `POST`

**FILE Parameters:**
- `file` (File)

---
### 🔹 `friend_chat.php`
**Method:** `GET`

**GET Parameters:**
- `uid`
- `iframe`

---
### 🔹 `getCarType.php`
**Method:** `GET`

---
### 🔹 `getCitiesByCountry.php`
**Method:** `POST`

**POST Parameters:**
- `CountryID`
- `UserLat`
- `UserLongt`

---
### 🔹 `getContactsFromPhoneNumbers.php`
**Method:** `POST`

**POST Parameters:**
- `PhoneNumbers`
- `UserID`

---
### 🔹 `getCountries.php`
**Method:** `GET`

---
### 🔹 `getFeedRecommendationAPI.php`
**Method:** `GET`

**GET Parameters:**
- `user_id`
- `limit`
- `offset`

---
### 🔹 `getKinzMadintyCategories.php`
**Method:** `GET`

---
### 🔹 `getKinzMadintyCategoriesInSideCategory.php`
**Method:** `POST`

**POST Parameters:**
- `CategoryId`

---
### 🔹 `getSearchHistory.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`

---
### 🔹 `getWallet.php`
**Method:** `POST`

**POST Parameters:**
- `DriverID`

---
### 🔹 `getWalletByWeeke.php`
**Method:** `POST`

**POST Parameters:**
- `DriverID`
- `Week`

---
### 🔹 `getWeight.php`
**Method:** `GET`

---
### 🔹 `get_cats.php`
**Method:** `GET`

---
### 🔹 `init_hotel_search.php`
**Method:** `GET`

---
### 🔹 `kenz.php`
**Method:** `GET`

**GET Parameters:**
- `cat`

---
### 🔹 `poll_hotel_search.php`
**Method:** `GET`

**GET Parameters:**
- `searchId`
- `dest`

---
### 🔹 `search_api.php`
**Method:** `GET`

**GET Parameters:**
- `q`

---
### 🔹 `search_esim.php`
**Method:** `GET`

**GET Parameters:**
- `country`
- `countryCode`

---
### 🔹 `search_flights.php`
**Method:** `GET`

**GET Parameters:**
- `origin`
- `destination`
- `depart_date`
- `return_date`
- `trip_class`

---
### 🔹 `search_hotels.php`
**Method:** `GET`

**GET Parameters:**
- `dest`

---
### 🔹 `server_check.php`
**Method:** `GET`

---
### 🔹 `test.php`
**Method:** `GET`

---
### 🔹 `uploadImageChat.php`
**Method:** `POST`

**POST Parameters:**
- `photochat`

---
### 🔹 `uploadsound.php`
**Method:** `POST`

**POST Parameters:**
- `Text`

---
### 🔹 `walletEradRased3.php`
**Method:** `GET`

**GET Parameters:**
- `Page`
- `ShopName`

---
## 📁 Orders

### 🔹 `AddOrder.php`
**Method:** `POST`

**POST Parameters:**
- `UserName`
- `UserPhone`
- `UserEmail`
- `UserAddress`
- `CarTypeID`
- `WeightsId`
- `UserCitiesID`
- `DestnationCitiesID`
- `UserCountryId`
- `DestnationCountryId`
- `UserID`
- `DestinationName`
- `DestnationLat`
- `DestnationLongt`
- `DestnationPhoto`
- `DestnationAddress`
- `OrderDetails`
- `UserLat`
- `UserLongt`
- `RealType`
- `OrderDelvTime`
- `OrderPriceFromShop`
- `ShopID`
- `Method`
- `OrderType`
- `ShowOrder`
- `ReadyTime`
- `Comment`
- `MaxDeliveryPrice`
- `FoodIDs`
- `usersddress`

---
### 🔹 `GetCategoryOfCancelledOrder.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`

---
### 🔹 `GetHistoryOrders.php`
**Method:** `POST`

**POST Parameters:**
- `UserId`

---
### 🔹 `GetOneOrdersDetails.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`

---
### 🔹 `GetOrdersInfo.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`

---
### 🔹 `GetUnratedOrders.php`
**Method:** `POST`

**POST Parameters:**
- `UserId`

---
### 🔹 `HasQoonOrder.php`
**Method:** `POST`

**POST Parameters:**
- `DelvryId`

---
### 🔹 `HasZeewenaOrder.php`
**Method:** `POST`

**POST Parameters:**
- `DelvryId`
- `DriverLongt`

---
### 🔹 `OrderFromDest.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`
- `DriverID`

---
### 🔹 `check_latest_order.php`
**Method:** `GET`

---
### 🔹 `getDriveCurrentOrders.php`
**Method:** `POST`

**POST Parameters:**
- `DelvryId`
- `Page`

---
### 🔹 `getDriveLiveOrders.php`
**Method:** `POST`

**POST Parameters:**
- `DelvryId`

---
### 🔹 `getZweenaOrders.php`
**Method:** `POST`

**POST Parameters:**
- `DriverLat`
- `DriverLongt`

---
### 🔹 `orders.php`
**Method:** `GET`

**GET Parameters:**
- `iframe`

---
## 📁 Driver

### 🔹 `AddReportDriver.php`
**Method:** `POST`

**POST Parameters:**
- `DriverID`
- `ReportContent`
- `ReportTitle`

---
### 🔹 `CancelOrderDriver.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`
- `AppType`

---
### 🔹 `ChangeLangDriver.php`
**Method:** `POST`

**POST Parameters:**
- `DriverID`
- `LANG`

---
### 🔹 `CheckCodeDriver.php`
**Method:** `POST`

**POST Parameters:**
- `DriverPhone`
- `UserFirebaseToken`
- `Code`

---
### 🔹 `CloseRateDriver.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`

---
### 🔹 `DriverHomeDetails.php`
**Method:** `POST`

**POST Parameters:**
- `DriverID`

---
### 🔹 `DriverRatesUser.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`
- `Rating`
- `Review`

---
### 🔹 `DriverSendInvoice.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`
- `FatoraDetails`
- `Price`

---
### 🔹 `DriverSendMessage.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`
- `UserID`
- `messsage`

---
### 🔹 `FinishOrderDriver.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`
- `DriverID`
- `AppType`

---
### 🔹 `GetAllServiceReviewDriver.php`
**Method:** `POST`

**POST Parameters:**
- `DriverId`

---
### 🔹 `GetDriverHome.php`
**Method:** `POST`

**POST Parameters:**
- `UserID`
- `DestinationName`
- `DestnationLat`
- `DestnationLongt`
- `DestnationPhoto`
- `DestnationAddress`
- `OrderDetails`
- `UserLat`
- `UserLongt`
- `OrderDelvTime`

---
### 🔹 `GetDriverInfo.php`
**Method:** `POST`

**POST Parameters:**
- `DriverID`

---
### 🔹 `GetDriverNearOrders.php`
**Method:** `POST`

**POST Parameters:**
- `DriverLat`
- `DriverLongt`

---
### 🔹 `GetDriverNearOrdersDist.php`
**Method:** `POST`

**POST Parameters:**
- `DriverLat`
- `DriverLongt`

---
### 🔹 `GetDriverNearOrdersUser.php`
**Method:** `POST`

**POST Parameters:**
- `DriverLat`
- `DriverLongt`

---
### 🔹 `GetDriverTransaction.php`
**Method:** `POST`

**POST Parameters:**
- `DriverID`
- `Type`

---
### 🔹 `GetDriverWallet.php`
**Method:** `POST`

**POST Parameters:**
- `DriverID`

---
### 🔹 `GetVersionCodeDriver.php`
**Method:** `GET`

---
### 🔹 `LogOrSignDriverSocial.php`
**Method:** `POST`

**POST Parameters:**
- `SocialID`
- `AccountType`
- `FirebaseDriverToken`

---
### 🔹 `LoginDriverJibler.php`
**Method:** `POST`

**POST Parameters:**
- `DriverPhone`
- `DriverPassword`
- `FirebaseDriverToken`

---
### 🔹 `LogoutDriverApi.php`
**Method:** `POST`

**POST Parameters:**
- `DriverID`

---
### 🔹 `PayWalletDriver.php`
**Method:** `POST`

**POST Parameters:**
- `DriverID`
- `Money`

---
### 🔹 `ResetPasswordDriver.php`
**Method:** `POST`

**POST Parameters:**
- `DriverPhone`
- `DriverPassword`

---
### 🔹 `SendCodeDriver.php`
**Method:** `POST`

**POST Parameters:**
- `PhoneNumber`
- `Code`
- `FirebaseDriverToken`

---
### 🔹 `UpdateDriverPosition.php`
**Method:** `POST`

**POST Parameters:**
- `CurrentLongt`
- `DriverID`
- `CurrentLat`
- `FirebaseDriverToken`

---
### 🔹 `UpdateDriverProfile.php`
**Method:** `POST`

**POST Parameters:**
- `DriverId`
- `Fname`
- `LName`
- `DriverEmail`
- `DriverPhone`
- `PersonalPhoto`
- `NationalIDPhoto`
- `CarPhoto`
- `licensePhoto`

---
### 🔹 `UpdateDriverProfileImage.php`
**Method:** `POST`

**POST Parameters:**
- `DriverId`
- `Name`
- `Lname`
- `DriverEmail`
- `PersonalPhoto`

---
### 🔹 `UpdateDriverProfileSocial.php`
**Method:** `POST`

**POST Parameters:**
- `DriverId`
- `Fname`
- `LName`
- `DriverEmail`
- `DriverPhone`
- `PersonalPhoto`
- `NationalIDPhoto`
- `NationalID`
- `CountryID`
- `SocialID`
- `AccountType`
- `CarPhoto`
- `licensePhoto`

---
### 🔹 `UpdateDriverProfileWithoutImage.php`
**Method:** `POST`

**POST Parameters:**
- `DriverId`
- `Name`
- `Lname`
- `DriverEmail`

---
### 🔹 `UserRateDriver (1).php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`
- `DriverID`
- `Rating`
- `Review`

---
### 🔹 `UserRateDriver.php`
**Method:** `POST`

**POST Parameters:**
- `OrderID`
- `DriverID`
- `Rating`
- `Review`
- `ShopID`
- `RatingShop`
- `ReviewShop`

---
### 🔹 `WhatsappDriverAPI.php`
**Method:** `POST`

**POST Parameters:**
- `PhoneNumber`

---
### 🔹 `confirmPayDriver.php`
**Method:** `POST`

**GET Parameters:**
- `DriverID`

**POST Parameters:**
- `Driver_id`

---
### 🔹 `getOneDriver.php`
**Method:** `POST`

**POST Parameters:**
- `DriverID`

---
### 🔹 `getUnratedOrdersDriver.php`
**Method:** `POST`

**POST Parameters:**
- `DriverID`

---
### 🔹 `getdriverNotification.php`
**Method:** `POST`

**POST Parameters:**
- `DelvryId`

---
### 🔹 `getdriverNotificationnotseennum.php`
**Method:** `POST`

**POST Parameters:**
- `DelvryId`

---
## 📁 Jibler

### 🔹 `GetJiblerCommesion.php`
**Method:** `GET`

---
