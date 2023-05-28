Ideas

Models:
Bank
Card
Transaction
ScheduledDonations

Money Places:
User Balance (wallet)
Organization
Givelist (splits up between organizations)

Transactions table:
id
correlation_id nullable
givelist_id nullable
transactable_source (one-to-many to Bank, User)
transactable_destination (one-to-many to User, Organization)




Bank Account -> User Balance (correlation_id)
Balance -> Organization (correlation_id, givelist_id)
Balance -> Organization (correlation_id, givelist_id)




Account = Bank/Credit/Debit Account (Users own multiple of these)
Wallet = User (Person) - Givelist Media is holding



User managing multiple Organizations (nonprofits)
Org isn't going to directly donate to another org (future maybe for grant making)
Good: Transaction from Charlie Account (-> Wallet) -> Organization
?: Transaction from Best Buy -> Organization
?: Transaction from Best Buy (Account) -> Charlie's Wallet (User) $100


Account -> Wallet (own user's or another's)
Account -> Onboarded Org
// Account -> Wallet -> Non Onboarded Org
// Account -> Wallet -> Givelist
Wallet -> Onboarded Org
Wallet -> Non Onboarded Org
Wallet -> Givelist
Wallet -> Wallet (user to user transfer)

public function accountToWallet(Account $account, User $user);
public function accountToOnboardedOrganization(Account $account, User $user);





Account -> ON Org - Transaction(Card/Bank, Organization) // always onboarded
Account -> NON Org - Transaction(Card/Bank, User) -> Transaction(User, Organization)



Dane's Translations (which could be wrong):

Transactions
* Only refers to bank account transactions
* One example from bank to wallet

User Organization Transfers (most important)
* Records every time a user gives to an organization
* One example from wallet to organization

User Events
* Jonathan wanted a list of all financial transactions
* Funds account
* Gives to Org
* Logging table

User Givings
* Contains all scheduled givings

Givelists Income
* Jonathan wanted stats for each givelist each time somebody gives money to a givelist, this gets an entry
