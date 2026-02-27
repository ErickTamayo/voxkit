import * as Types from '../../../../../graphql/types';

import { TypedDocumentNode as DocumentNode } from '@graphql-typed-document-node/core';
export type OverviewActivitiesTabBadgeQueryVariables = Types.Exact<{
  where?: Types.InputMaybe<Types.QueryActivitiesWhereWhereConditions>;
}>;


export type OverviewActivitiesTabBadgeQuery = { __typename?: 'Query', activities: { __typename: 'ActivityPaginator', paginatorInfo: { __typename: 'PaginatorInfo', total: number } } };


export const OverviewActivitiesTabBadgeDocument = {"kind":"Document","definitions":[{"kind":"OperationDefinition","operation":"query","name":{"kind":"Name","value":"OverviewActivitiesTabBadge"},"variableDefinitions":[{"kind":"VariableDefinition","variable":{"kind":"Variable","name":{"kind":"Name","value":"where"}},"type":{"kind":"NamedType","name":{"kind":"Name","value":"QueryActivitiesWhereWhereConditions"}}}],"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"activities"},"arguments":[{"kind":"Argument","name":{"kind":"Name","value":"where"},"value":{"kind":"Variable","name":{"kind":"Name","value":"where"}}}],"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"__typename"}},{"kind":"Field","name":{"kind":"Name","value":"paginatorInfo"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"__typename"}},{"kind":"Field","name":{"kind":"Name","value":"total"}}]}}]}}]}}]} as unknown as DocumentNode<OverviewActivitiesTabBadgeQuery, OverviewActivitiesTabBadgeQueryVariables>;