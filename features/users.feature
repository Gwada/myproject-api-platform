Feature: Manage users
  In order to manage users
  As a client software developer
  I need to be able to retrieve, create, update and delete them through the API.

  # the "@createSchema" annotation provided by API Platform creates a temporary SQLite database for testing the API
  @createSchema
  Scenario: Create a user
    Given I load fixtures from folder "DataFixtures/ORM/"
    And I am authenticated as "behat.admin" with "Password-123" password
    And I add "Accept" header equal to "application/json"
    When I send a "POST" request to protected "/api/users" with body:
    """
    {
      "login": "t.lallement",
      "plainPassword": "OSaHotwegda2:ngyib15",
      "name": "Thomas Lallement",
      "userLevel": "ADMIN",
      "active": true
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "id": 2,
      "login": "t.lallement",
      "name": "Thomas Lallement",
      "phone": null,
      "userLevel": "ADMIN",
      "active": true,
      "lastLoginDate": null
    }
    """
    And the password "OSaHotwegda2:ngyib15" should be valid for the user "2"

  Scenario: Update a user without password modification
    Given I am authenticated as "behat.admin" with "Password-123" password
    And I add "Accept" header equal to "application/json"
    When I send a "PUT" request to protected "/api/users/2" with body:
    """
    {
      "phone": "-"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "id": 2,
      "login": "t.lallement",
      "name": "Thomas Lallement",
      "phone": "-",
      "userLevel": "ADMIN",
      "active": true,
      "lastLoginDate": null
    }
    """
    And the password "OSaHotwegda2:ngyib15" should be valid for the user "2"

  Scenario: Update a user with password modification
    Given I am authenticated as "behat.admin" with "Password-123" password
    And I add "Accept" header equal to "application/json"
    When I send a "PUT" request to protected "/api/users/2" with body:
    """
    {
      "plainPassword": "5sAw@ssqADSzqx45"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "id": 2,
      "login": "t.lallement",
      "name": "Thomas Lallement",
      "phone": "-",
      "userLevel": "ADMIN",
      "active": true,
      "lastLoginDate": null
    }
    """
    And the password "5sAw@ssqADSzqx45" should be valid for the user "2"

  Scenario: Update a user with password too short
    Given I am authenticated as "behat.admin" with "Password-123" password
    And I add "Accept" header equal to "application/json"
    When I send a "PUT" request to protected "/api/users/2" with body:
    """
    {
      "plainPassword": "test"
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
        "type": "https://tools.ietf.org/html/rfc2616#section-10",
        "title": "An error occurred",
        "detail": "plainPassword: This value is too short. It should have 8 characters or more.\nplainPassword: This value doesn't respect the password policy. The password has to contain at least one small letter, one capital letter, one number and one special character (? ! - _ + : @).",
        "violations": [
            {
                "propertyPath": "plainPassword",
                "message": "This value is too short. It should have 8 characters or more."
            },
            {
                "propertyPath": "plainPassword",
                "message": "This value doesn't respect the password policy. The password has to contain at least one small letter, one capital letter, one number and one special character (? ! - _ + : @)."
            }
        ]
    }
    """
    And the password "5sAw@ssqADSzqx45" should be valid for the user "2"

  Scenario: Try to create a user with same login that another one
    Given I am authenticated as "behat.admin" with "Password-123" password
    And I add "Accept" header equal to "application/json"
    When I send a "POST" request to protected "/api/users" with body:
    """
    {
      "login": "t.lallement",
      "plainPassword": "981-2Szqx45",
      "name": "Thomas Lallement",
      "userLevel": "ADMIN",
      "active": true
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "type": "https:\/\/tools.ietf.org\/html\/rfc2616#section-10",
      "title": "An error occurred",
      "detail": "login: This value is already used.",
      "violations": [
          {
              "propertyPath": "login",
              "message": "This value is already used."
          }
      ]
    }
    """

  Scenario: Get users
    Given I am authenticated as "behat.admin" with "Password-123" password
    And I add "Accept" header equal to "application/json"
    When I send a "GET" request to protected "/api/users"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON with pattern should be equal to:
    """
    [
      {
          "id": 1,
          "login": "behat.admin",
          "name": "Admin",
          "phone": null,
          "userLevel": "ADMIN",
          "active": true,
          "lastLoginDate": "(\\d{4})-(\\d{2})-(\\d{2})T(\\d{2})\\:(\\d{2})\\:(\\d{2})[+-](\\d{2})\\:(\\d{2})"
      },
      {
          "id": 2,
          "login": "t.lallement",
          "name": "Thomas Lallement",
          "phone": "-",
          "userLevel": "ADMIN",
          "active": true,
          "lastLoginDate": null
      }
    ]
    """
